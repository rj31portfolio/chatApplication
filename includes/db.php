<?php
/**
 * Database connection and helper functions
 */

require_once __DIR__ . '/config.php';

/**
 * Get database connection
 * 
 * @return mysqli Database connection
 */
function getDbConnection() {
    static $conn;
    
    if (!$conn) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            die("Database connection failed. Please try again later.");
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/**
 * Execute a database query
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @param string $types Types of parameters (i:int, d:double, s:string, b:blob)
 * @return mysqli_stmt|false Prepared statement
 */
function dbQuery($sql, $params = [], $types = null) {
    $conn = getDbConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Query preparation failed: " . $conn->error . " SQL: " . $sql);
        return false;
    }
    
    if (!empty($params)) {
        if ($types === null) {
            $types = str_repeat('s', count($params));
        }
        
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt;
}

/**
 * Execute a select query and return results as an array
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @param string $types Types of parameters
 * @return array|false Results array or false on failure
 */
function dbSelect($sql, $params = [], $types = null) {
    $stmt = dbQuery($sql, $params, $types);
    
    if (!$stmt) {
        return false;
    }
    
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    return $data;
}

/**
 * Execute an insert, update, or delete query
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @param string $types Types of parameters
 * @return bool|int Affected rows or insert ID, or false on failure
 */
function dbExecute($sql, $params = [], $types = null) {
    $stmt = dbQuery($sql, $params, $types);
    
    if (!$stmt) {
        return false;
    }
    
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = ($stmt->affected_rows > 0);
    
    // If it was an INSERT, return the insert ID
    if (strpos(strtoupper($sql), 'INSERT') === 0) {
        $result = $stmt->insert_id;
    } else {
        $result = $stmt->affected_rows;
    }
    
    $stmt->close();
    return $result;
}

/**
 * Get a single row from the database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|null Single row or null if not found
 */
function dbGetRow($sql, $params = []) {
    $result = dbSelect($sql, $params);
    
    if ($result && count($result) > 0) {
        return $result[0];
    }
    
    return null;
}

/**
 * Get a single value from the database
 * 
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return mixed|null Single value or null if not found
 */
function dbGetValue($sql, $params = []) {
    $row = dbGetRow($sql, $params);
    
    if ($row) {
        return reset($row);
    }
    
    return null;
}

/**
 * Create database tables if they don't exist
 */
function createDatabaseTables() {
    $conn = getDbConnection();
    
    // Create user role type if it doesn't exist
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN
            CREATE TYPE user_role AS ENUM ('super_admin', 'admin');
        END IF;
    END
    $$;
    ");
    
    // Create business type if it doesn't exist
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'business_type') THEN
            CREATE TYPE business_type AS ENUM ('restaurant', 'ecommerce', 'service', 'healthcare', 'education', 'finance', 'other');
        END IF;
    END
    $$;
    ");
    
    // Users table (for admins and super-admins)
    pg_query($conn, "
    CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        role user_role NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ");
    
    // Add trigger for updated_at
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'update_users_timestamp') THEN
            CREATE OR REPLACE FUNCTION update_timestamp()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            
            CREATE TRIGGER update_users_timestamp
            BEFORE UPDATE ON users
            FOR EACH ROW
            EXECUTE PROCEDURE update_timestamp();
        END IF;
    END
    $$;
    ");
    
    // Businesses table
    pg_query($conn, "
    CREATE TABLE IF NOT EXISTS businesses (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        business_type business_type NOT NULL,
        api_key VARCHAR(64) NOT NULL UNIQUE,
        admin_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ");
    
    // Add trigger for updated_at
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'update_businesses_timestamp') THEN
            CREATE TRIGGER update_businesses_timestamp
            BEFORE UPDATE ON businesses
            FOR EACH ROW
            EXECUTE PROCEDURE update_timestamp();
        END IF;
    END
    $$;
    ");
    
    // Chatbot responses table
    pg_query($conn, "
    CREATE TABLE IF NOT EXISTS responses (
        id SERIAL PRIMARY KEY,
        business_id INTEGER NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
        intent VARCHAR(50) NOT NULL,
        pattern TEXT NOT NULL,
        response TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ");
    
    // Add trigger for updated_at
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'update_responses_timestamp') THEN
            CREATE TRIGGER update_responses_timestamp
            BEFORE UPDATE ON responses
            FOR EACH ROW
            EXECUTE PROCEDURE update_timestamp();
        END IF;
    END
    $$;
    ");
    
    // Create index on business_id and intent
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1 FROM pg_indexes 
            WHERE tablename = 'responses' AND indexname = 'responses_business_intent_idx'
        ) THEN
            CREATE INDEX responses_business_intent_idx ON responses(business_id, intent);
        END IF;
    END
    $$;
    ");
    
    // Sessions table for user tracking
    pg_query($conn, "
    CREATE TABLE IF NOT EXISTS chat_sessions (
        id SERIAL PRIMARY KEY,
        session_id VARCHAR(64) NOT NULL,
        business_id INTEGER NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
        visitor_ip VARCHAR(45),
        user_agent TEXT,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ended_at TIMESTAMP NULL
    );
    ");
    
    // Create index on session_id
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1 FROM pg_indexes 
            WHERE tablename = 'chat_sessions' AND indexname = 'chat_sessions_session_id_idx'
        ) THEN
            CREATE INDEX chat_sessions_session_id_idx ON chat_sessions(session_id);
        END IF;
    END
    $$;
    ");
    
    // Messages table
    pg_query($conn, "
    CREATE TABLE IF NOT EXISTS messages (
        id SERIAL PRIMARY KEY,
        session_id INTEGER NOT NULL REFERENCES chat_sessions(id) ON DELETE CASCADE,
        message TEXT NOT NULL,
        is_bot BOOLEAN NOT NULL DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ");
    
    // Create index on session_id
    pg_query($conn, "
    DO $$
    BEGIN
        IF NOT EXISTS (
            SELECT 1 FROM pg_indexes 
            WHERE tablename = 'messages' AND indexname = 'messages_session_id_idx'
        ) THEN
            CREATE INDEX messages_session_id_idx ON messages(session_id);
        END IF;
    END
    $$;
    ");
}
