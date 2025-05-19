 -- AI Chatbot System MySQL Database Setup

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS ai_chatbot;
USE ai_chatbot;

-- Users table (for admins and super-admins)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('super_admin', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Businesses table
CREATE TABLE IF NOT EXISTS businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    business_type ENUM('restaurant', 'ecommerce', 'service', 'healthcare', 'education', 'finance', 'other') NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    admin_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chatbot responses table
CREATE TABLE IF NOT EXISTS responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    intent VARCHAR(50) NOT NULL,
    pattern TEXT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX (business_id, intent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions table for user tracking
CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    business_id INT NOT NULL,
    visitor_ip VARCHAR(45),
    user_agent TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    message TEXT NOT NULL,
    is_bot TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    INDEX (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for testing

-- Create super admin user (password: admin123)
INSERT INTO users (username, password, email, role)
VALUES ('admin', '$2y$12$1wCi27UnM/yKbAWYhbE1S.HOGCDxyOLKCpRzU7oXEo0k5.QTAXi5.', 'admin@example.com', 'super_admin');

-- Create regular admin user (password: password123)
INSERT INTO users (username, password, email, role)
VALUES ('business_admin', '$2y$12$lH8Z4Mbd/d5CDZrZwHjq8eQY9RgAKTL1vWDMP01XCvCT1.nYVJtJe', 'business@example.com', 'admin');

-- Create sample businesses
INSERT INTO businesses (name, business_type, api_key, admin_id)
VALUES 
('Example Restaurant', 'restaurant', '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef', 2),
('Sample E-commerce', 'ecommerce', 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890', NULL);

-- Create sample responses for the restaurant
INSERT INTO responses (business_id, intent, pattern, response)
VALUES 
(1, 'hours', 'opening hours,hours,when are you open,business hours', 'We are open Monday to Friday from 11 AM to 10 PM, and weekends from 10 AM to 11 PM.'),
(1, 'menu', 'menu,food,dishes,specials,what do you serve', 'Our menu features a variety of dishes including pasta, steaks, seafood, and vegetarian options. Would you like to hear about our daily specials?'),
(1, 'reservation', 'reservation,book,reserve,table,booking', 'We''d be happy to make a reservation for you. Please let me know the date, time, and number of people in your party.'),
(1, 'location', 'location,address,directions,where are you,how to get there', 'We''re located at 123 Main Street, Downtown. Parking is available in the back.'),
(1, 'delivery', 'delivery,takeout,take out,carry out,order online', 'Yes, we offer delivery through our website or you can call us at (555) 123-4567 to place a takeout order.');

-- Create sample responses for the e-commerce site
INSERT INTO responses (business_id, intent, pattern, response)
VALUES 
(2, 'shipping', 'shipping,delivery,shipping cost,how long,when will I receive', 'We offer free shipping on orders over $50. Standard shipping takes 3-5 business days, and express shipping takes 1-2 business days.'),
(2, 'returns', 'return,refund,exchange,send back,return policy', 'Our return policy allows you to return any unused items within 30 days of purchase for a full refund or exchange.'),
(2, 'products', 'products,catalog,items,inventory,what do you sell', 'We offer a wide range of products including clothing, accessories, home goods, and electronics. Is there a specific category you''re interested in?'),
(2, 'payment', 'payment,pay,credit card,payment methods,accepted cards', 'We accept all major credit cards, PayPal, and Apple Pay as payment methods.'),
(2, 'track', 'track,order status,where is my order,tracking', 'You can track your order by logging into your account or using the tracking number provided in your order confirmation email.');
