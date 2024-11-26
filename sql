-- Create customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    product_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_details JSON NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Create meal table
CREATE TABLE meal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_id INT NOT NULL,  -- This links to customer_id in the meal_registration table
    lunch_meal BOOLEAN DEFAULT FALSE,
    dinner_meal BOOLEAN DEFAULT FALSE,
    deposit DECIMAL(10, 2) DEFAULT 0,
    meal_price DECIMAL(10, 2),
    remain_balance DECIMAL(10, 2),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_id) REFERENCES meal_registration(customer_id) ON DELETE CASCADE
);

-- Create meal_registration table with additional fields
CREATE TABLE meal_registration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    deposit DECIMAL(10, 2) DEFAULT 100.00,
    varsity_id VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    meal_date DATE NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);



CREATE TABLE deposit_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    deposit_amount DECIMAL(10, 2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES meal_registration(customer_id) ON DELETE CASCADE
);

CREATE TABLE `offers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `offer_name` VARCHAR(255) NOT NULL,
  `discount_code` VARCHAR(50) NOT NULL,
  `discount_amount` INT(11) NOT NULL,
  `expiry_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
