<?php
/**
 * Setup Database and Initial Admin User
 * This script will create the necessary database tables and a default admin user
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Set initial admin credentials
$adminUsername = 'admin';
$adminPassword = 'admin123';
$adminEmail = 'admin@example.com';
$adminRole = 'super_admin';

// Initialize success and error messages
$success = false;
$messages = [];

// Create database tables
try {
    createDatabaseTables();
    $messages[] = [
        'type' => 'success',
        'text' => 'Database tables created successfully'
    ];
} catch (Exception $e) {
    $messages[] = [
        'type' => 'danger',
        'text' => 'Error creating database tables: ' . $e->getMessage()
    ];
}

// Check if super admin user already exists
$userExists = dbGetValue("SELECT COUNT(*) FROM users WHERE role = ?", ['super_admin']);

if (!$userExists) {
    // Create super admin user
    try {
        $userId = createUser($adminUsername, $adminPassword, $adminEmail, $adminRole);
        if ($userId !== false) {
            $messages[] = [
                'type' => 'success',
                'text' => 'Super admin user created successfully (Username: ' . $adminUsername . ', Password: ' . $adminPassword . ')'
            ];
            $success = true;
        } else {
            $messages[] = [
                'type' => 'danger',
                'text' => 'Error creating super admin user'
            ];
        }
    } catch (Exception $e) {
        $messages[] = [
            'type' => 'danger',
            'text' => 'Error creating super admin user: ' . $e->getMessage()
        ];
    }
} else {
    $messages[] = [
        'type' => 'info',
        'text' => 'Super admin user already exists'
    ];
    $success = true;
}

// Create example data if database setup was successful
if ($success) {
    // Check if businesses table is empty
    $businessCount = dbGetValue("SELECT COUNT(*) FROM businesses");
    
    if ($businessCount == 0) {
        try {
            // Create an example admin user
            $adminId = createUser('business_admin', 'password123', 'business@example.com', 'admin');
            
            if ($adminId !== false) {
                // Create an example business
                $apiKey = generateApiKey();
                $businessId = dbExecute(
                    "INSERT INTO businesses (name, business_type, api_key, admin_id) VALUES (?, ?, ?, ?)",
                    ['Example Restaurant', 'restaurant', $apiKey, $adminId]
                );
                
                if ($businessId !== false) {
                    // Create some example responses
                    $responses = [
                        ['hours', 'opening hours,hours,when are you open,business hours', 'We are open Monday to Friday from 11 AM to 10 PM, and weekends from 10 AM to 11 PM.'],
                        ['menu', 'menu,food,dishes,specials,what do you serve', 'Our menu features a variety of dishes including pasta, steaks, seafood, and vegetarian options. Would you like to hear about our daily specials?'],
                        ['reservation', 'reservation,book,reserve,table,booking', 'We\'d be happy to make a reservation for you. Please let me know the date, time, and number of people in your party.'],
                        ['location', 'location,address,directions,where are you,how to get there', 'We\'re located at 123 Main Street, Downtown. Parking is available in the back.'],
                        ['delivery', 'delivery,takeout,take out,carry out,order online', 'Yes, we offer delivery through our website or you can call us at (555) 123-4567 to place a takeout order.']
                    ];
                    
                    foreach ($responses as $response) {
                        dbExecute(
                            "INSERT INTO responses (business_id, intent, pattern, response) VALUES (?, ?, ?, ?)",
                            [$businessId, $response[0], $response[1], $response[2]]
                        );
                    }
                    
                    $messages[] = [
                        'type' => 'success',
                        'text' => 'Example data created successfully'
                    ];
                }
            }
        } catch (Exception $e) {
            $messages[] = [
                'type' => 'warning',
                'text' => 'Error creating example data: ' . $e->getMessage()
            ];
        }
    } else {
        $messages[] = [
            'type' => 'info',
            'text' => 'Example data already exists'
        ];
    }
}

// Create a marker file to indicate installation is complete
if ($success) {
    file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
}

// Display the setup page
$pageTitle = 'AI Chatbot System - Database Setup';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        
        .setup-container {
            max-width: 700px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="logo">
                <i class="fas fa-robot fa-4x text-primary"></i>
                <h1 class="mt-3">AI Chatbot System</h1>
                <p class="text-muted">Database Setup</p>
            </div>
            
            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($message['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
            
            <?php if ($success): ?>
                <div class="text-center mt-4">
                    <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                    <h2>Setup Complete!</h2>
                    <p>Your AI Chatbot System is now ready to use.</p>
                    <div class="d-grid gap-2 mt-4">
                        <a href="index.php" class="btn btn-primary btn-lg">Go to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center mt-4">
                    <i class="fas fa-times-circle fa-5x text-danger mb-3"></i>
                    <h2>Setup Failed</h2>
                    <p>There were errors during the setup process. Please check the messages above.</p>
                    <div class="d-grid gap-2 mt-4">
                        <a href="setup_database.php" class="btn btn-primary btn-lg">Try Again</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>