CREATE DATABASE IF NOT EXISTS orders_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE orders_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(100) DEFAULT NULL,
    role ENUM('admin','customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'unit',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Default admin account  password: admin123
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin', 'admin@ordersys.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample product catalog
INSERT IGNORE INTO products (name, description, price, stock, unit) VALUES
('Office Chair',       'Ergonomic chair with lumbar support and armrests',    299.99,  50, 'unit'),
('Standing Desk',      'Height-adjustable sit-stand desk, 140x70cm',          599.99,  20, 'unit'),
('Laptop Stand',       'Adjustable aluminum laptop stand, foldable',           49.99, 100, 'unit'),
('Wireless Mouse',     'Ergonomic wireless mouse, 2.4GHz, 1600 DPI',           39.99, 150, 'unit'),
('Mechanical Keyboard','Compact TKL mechanical keyboard, blue switches',       129.99,  75, 'unit'),
('Monitor 27"',        '4K IPS 27-inch monitor, 99% sRGB, USB-C',             449.99,  30, 'unit'),
('USB-C Hub 7-in-1',   'HDMI 4K + 3x USB-A + SD + MicroSD + PD 100W',         59.99, 200, 'unit'),
('Webcam HD 1080p',    '1080p webcam with built-in noise-cancelling mic',       89.99,  80, 'unit'),
('Desk Lamp LED',      'Touch-control LED desk lamp, 5 colour temps',          34.99, 120, 'unit'),
('Cable Organiser Kit','Set of 10 reusable cable ties + 2 cable boxes',        19.99, 300, 'set');
