-- Create database
CREATE DATABASE IF NOT EXISTS salon_management;
USE salon_management;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$8K1p/a0dR1U5bN7F8z5vE.5z5z5z5z5z5z5z5z5z5z5z5z5z5z5');

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    date_of_birth DATE,
    anniversary_date DATE,
    membership_id INT,
    loyalty_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample customers
INSERT INTO customers (name, email, phone, address) VALUES
('John Doe', 'john@example.com', '1234567890', '123 Main St'),
('Jane Smith', 'jane@example.com', '0987654321', '456 Oak Ave'),
('Mike Johnson', 'mike@example.com', '5555555555', '789 Pine Rd'),
('Sarah Williams', 'sarah@example.com', '4444444444', '321 Elm St'),
('David Brown', 'david@example.com', '3333333333', '654 Maple Dr');

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample services
INSERT INTO services (name, description, price, duration) VALUES
('Haircut', 'Basic haircut service', 30.00, 30),
('Hair Coloring', 'Full hair coloring service', 80.00, 120),
('Hair Styling', 'Professional hair styling', 40.00, 45),
('Manicure', 'Basic manicure service', 25.00, 30),
('Pedicure', 'Basic pedicure service', 35.00, 45),
('Facial', 'Basic facial treatment', 50.00, 60),
('Massage', 'Full body massage', 70.00, 60),
('Makeup', 'Professional makeup application', 45.00, 45);

-- Staff table
CREATE TABLE IF NOT EXISTS staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    specialization VARCHAR(100),
    role ENUM('admin', 'manager', 'stylist', 'technician', 'assistant') NOT NULL DEFAULT 'stylist',
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample staff
INSERT INTO staff (name, email, phone, specialization) VALUES
('Emma Wilson', 'emma@salon.com', '1111111111', 'Hair Stylist'),
('James Taylor', 'james@salon.com', '2222222222', 'Color Specialist'),
('Lisa Anderson', 'lisa@salon.com', '3333333333', 'Nail Technician'),
('Robert Martinez', 'robert@salon.com', '4444444444', 'Massage Therapist'),
('Maria Garcia', 'maria@salon.com', '5555555555', 'Makeup Artist');

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no-show', 'walk-in') DEFAULT 'scheduled',
    notes TEXT,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_pattern ENUM('daily', 'weekly', 'biweekly', 'monthly') DEFAULT NULL,
    recurring_end_date DATE DEFAULT NULL,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Reminders table
CREATE TABLE IF NOT EXISTS reminders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    reminder_type ENUM('email', 'sms') NOT NULL,
    reminder_time DATETIME NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);

-- Online bookings table
CREATE TABLE IF NOT EXISTS online_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);

-- Insert sample appointments (last 30 days)
INSERT INTO appointments (customer_id, service_id, staff_id, appointment_date, appointment_time, status) VALUES
-- Haircut appointments
(1, 1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'completed'),
(2, 1, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '11:00:00', 'completed'),
(3, 1, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '14:00:00', 'completed'),
(4, 1, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '15:00:00', 'completed'),
(5, 1, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '16:00:00', 'completed'),

-- Hair coloring appointments
(1, 2, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'completed'),
(2, 2, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '13:00:00', 'completed'),
(3, 2, 2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '14:00:00', 'completed'),

-- Hair styling appointments
(4, 3, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '11:00:00', 'completed'),
(5, 3, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '15:00:00', 'completed'),

-- Manicure appointments
(1, 4, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:00:00', 'completed'),
(2, 4, 3, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '11:00:00', 'completed'),
(3, 4, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '14:00:00', 'completed'),

-- Pedicure appointments
(4, 5, 3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '13:00:00', 'completed'),
(5, 5, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '15:00:00', 'completed'),

-- Facial appointments
(1, 6, 4, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '10:00:00', 'completed'),
(2, 6, 4, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '11:00:00', 'completed'),

-- Massage appointments
(3, 7, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'completed'),
(4, 7, 4, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '15:00:00', 'completed'),

-- Makeup appointments
(5, 8, 5, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:00:00', 'completed'),
(1, 8, 5, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '11:00:00', 'completed'),

-- Some cancelled and no-show appointments
(2, 1, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '10:00:00', 'cancelled'),
(3, 2, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), '13:00:00', 'no-show'),
(4, 3, 1, DATE_SUB(CURDATE(), INTERVAL 8 DAY), '14:00:00', 'cancelled'),
(5, 4, 3, DATE_SUB(CURDATE(), INTERVAL 9 DAY), '15:00:00', 'no-show');

-- Create indexes for better performance
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_services_price ON services(price);
CREATE INDEX idx_staff_specialization ON staff(specialization);
CREATE INDEX idx_appointments_recurring ON appointments(is_recurring, recurring_pattern);
CREATE INDEX idx_reminders_time ON reminders(reminder_time);
CREATE INDEX idx_online_bookings_reference ON online_bookings(booking_reference);

-- Customer preferences table
CREATE TABLE IF NOT EXISTS customer_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    preference_type ENUM('service', 'product', 'staff', 'style') NOT NULL,
    preference_value VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Customer service history table
CREATE TABLE IF NOT EXISTS customer_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Memberships table
CREATE TABLE IF NOT EXISTS memberships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in months',
    benefits TEXT,
    points_multiplier DECIMAL(3,2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customer memberships table
CREATE TABLE IF NOT EXISTS customer_memberships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    membership_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (membership_id) REFERENCES memberships(id)
);

-- Loyalty transactions table
CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    points INT NOT NULL,
    transaction_type ENUM('earn', 'redeem') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Insert sample memberships
INSERT INTO memberships (name, description, price, duration, benefits, points_multiplier) VALUES
('Basic', 'Basic membership with standard benefits', 29.99, 1, '10% off services, 1.0x points', 1.00),
('Premium', 'Premium membership with enhanced benefits', 49.99, 1, '20% off services, 1.5x points, Free birthday service', 1.50),
('VIP', 'VIP membership with exclusive benefits', 99.99, 1, '30% off services, 2.0x points, Free birthday service, Priority booking', 2.00);

-- Staff schedules table
CREATE TABLE IF NOT EXISTS staff_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_working_day BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Staff attendance table
CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    date DATE NOT NULL,
    clock_in DATETIME,
    clock_out DATETIME,
    status ENUM('present', 'absent', 'late', 'half_day', 'leave') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Staff leaves table
CREATE TABLE IF NOT EXISTS staff_leaves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    leave_type ENUM('annual', 'sick', 'personal', 'maternity', 'paternity', 'unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reason TEXT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (approved_by) REFERENCES staff(id)
);

-- Staff commissions table
CREATE TABLE IF NOT EXISTS staff_commissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Staff performance metrics table
CREATE TABLE IF NOT EXISTS staff_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    metric_date DATE NOT NULL,
    total_appointments INT DEFAULT 0,
    completed_appointments INT DEFAULT 0,
    cancelled_appointments INT DEFAULT 0,
    no_show_appointments INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0.00,
    average_rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Insert sample staff schedules
INSERT INTO staff_schedules (staff_id, day_of_week, start_time, end_time) VALUES
(1, 'monday', '09:00:00', '17:00:00'),
(1, 'tuesday', '09:00:00', '17:00:00'),
(1, 'wednesday', '09:00:00', '17:00:00'),
(1, 'thursday', '09:00:00', '17:00:00'),
(1, 'friday', '09:00:00', '17:00:00'),
(2, 'monday', '10:00:00', '18:00:00'),
(2, 'tuesday', '10:00:00', '18:00:00'),
(2, 'wednesday', '10:00:00', '18:00:00'),
(2, 'thursday', '10:00:00', '18:00:00'),
(2, 'friday', '10:00:00', '18:00:00');

-- Insert sample staff attendance
INSERT INTO staff_attendance (staff_id, date, clock_in, clock_out, status) VALUES
(1, CURDATE(), DATE_FORMAT(NOW(), '%Y-%m-%d 09:00:00'), DATE_FORMAT(NOW(), '%Y-%m-%d 17:00:00'), 'present'),
(2, CURDATE(), DATE_FORMAT(NOW(), '%Y-%m-%d 10:00:00'), DATE_FORMAT(NOW(), '%Y-%m-%d 18:00:00'), 'present');

-- Insert sample staff leaves
INSERT INTO staff_leaves (staff_id, leave_type, start_date, end_date, status, reason) VALUES
(1, 'annual', DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'pending', 'Family vacation'),
(2, 'sick', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'approved', 'Medical appointment');

-- Insert sample staff commissions
INSERT INTO staff_commissions (staff_id, appointment_id, service_id, amount, commission_amount, status) VALUES
(1, 1, 1, 30.00, 6.00, 'pending'),
(2, 2, 2, 80.00, 16.00, 'pending');

-- Insert sample staff performance
INSERT INTO staff_performance (staff_id, metric_date, total_appointments, completed_appointments, total_revenue, average_rating) VALUES
(1, CURDATE(), 10, 8, 300.00, 4.5),
(2, CURDATE(), 8, 7, 560.00, 4.8);

-- Billing table
CREATE TABLE IF NOT EXISTS billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    customer_id INT NOT NULL,
    staff_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Invoice table
CREATE TABLE IF NOT EXISTS invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    billing_id INT NOT NULL,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (billing_id) REFERENCES billing(id)
);

-- Billing items table
CREATE TABLE IF NOT EXISTS billing_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    billing_id INT NOT NULL,
    service_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (billing_id) REFERENCES billing(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    billing_id INT NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'mobile_payment', 'bank_transfer') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(100),
    payment_date DATETIME NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (billing_id) REFERENCES billing(id)
);

-- Discounts table
CREATE TABLE IF NOT EXISTS discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    minimum_purchase DECIMAL(10,2) DEFAULT 0.00,
    maximum_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    times_used INT DEFAULT 0,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tax rates table
CREATE TABLE IF NOT EXISTS tax_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default tax rate
INSERT INTO tax_rates (name, rate, description, is_default) VALUES
('Standard VAT', 20.00, 'Standard Value Added Tax', TRUE);

-- Insert sample discounts
INSERT INTO discounts (code, description, discount_type, discount_value, start_date, end_date, minimum_purchase, usage_limit) VALUES
('WELCOME10', 'Welcome discount 10% off', 'percentage', 10.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 50.00, 100),
('SUMMER20', 'Summer special 20% off', 'percentage', 20.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 MONTH), 100.00, 50),
('FIXED15', 'Fixed $15 off', 'fixed', 15.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 75.00, 200);

-- Create indexes for better performance
CREATE INDEX idx_billing_appointment ON billing(appointment_id);
CREATE INDEX idx_billing_customer ON billing(customer_id);
CREATE INDEX idx_invoice_number ON invoices(invoice_number);
CREATE INDEX idx_payments_billing ON payments(billing_id);
CREATE INDEX idx_discounts_code ON discounts(code);
CREATE INDEX idx_tax_rates_status ON tax_rates(status);

-- Service packages table
CREATE TABLE IF NOT EXISTS service_packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Package services table
CREATE TABLE IF NOT EXISTS package_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_id INT NOT NULL,
    service_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES service_packages(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    brand VARCHAR(50),
    unit_price DECIMAL(10,2) NOT NULL,
    retail_price DECIMAL(10,2) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    barcode VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory table
CREATE TABLE IF NOT EXISTS inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 10,
    reorder_quantity INT NOT NULL DEFAULT 20,
    last_restock_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Inventory transactions table
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'adjustment', 'return') NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    reference_id VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    tax_id VARCHAR(50),
    payment_terms VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Purchase orders table
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    status ENUM('draft', 'ordered', 'received', 'cancelled') DEFAULT 'draft',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

-- Purchase order items table
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert sample service packages
INSERT INTO service_packages (name, description, price, duration, discount_percentage) VALUES
('Hair Care Package', 'Complete hair care package including cut, color, and styling', 150.00, 180, 15.00),
('Beauty Package', 'Full beauty package including facial, manicure, and pedicure', 120.00, 150, 10.00),
('Spa Package', 'Relaxing spa package including massage and facial', 200.00, 180, 20.00);

-- Insert sample products
INSERT INTO products (name, description, category, brand, unit_price, retail_price, sku) VALUES
('Shampoo', 'Professional hair shampoo', 'Hair Care', 'Salon Pro', 15.00, 25.00, 'SH001'),
('Conditioner', 'Professional hair conditioner', 'Hair Care', 'Salon Pro', 15.00, 25.00, 'CO001'),
('Hair Color', 'Professional hair color', 'Hair Color', 'Color Plus', 20.00, 35.00, 'HC001'),
('Nail Polish', 'Professional nail polish', 'Nail Care', 'Nail Art', 8.00, 15.00, 'NP001'),
('Facial Cleanser', 'Professional facial cleanser', 'Skin Care', 'Skin Care Pro', 18.00, 30.00, 'FC001');

-- Insert sample inventory
INSERT INTO inventory (product_id, quantity, reorder_level, reorder_quantity) VALUES
(1, 50, 10, 20),
(2, 45, 10, 20),
(3, 30, 5, 15),
(4, 100, 20, 50),
(5, 40, 10, 20);

-- Insert sample vendors
INSERT INTO vendors (name, contact_person, email, phone, address) VALUES
('Beauty Supplies Co.', 'John Smith', 'john@beautysupplies.com', '555-0101', '123 Supply St'),
('Hair Products Inc.', 'Sarah Johnson', 'sarah@hairproducts.com', '555-0102', '456 Product Ave'),
('Nail Art Supplies', 'Mike Brown', 'mike@nailart.com', '555-0103', '789 Nail Rd');

-- Create indexes for better performance
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_inventory_product ON inventory(product_id);
CREATE INDEX idx_inventory_transactions_product ON inventory_transactions(product_id);
CREATE INDEX idx_vendors_status ON vendors(status);
CREATE INDEX idx_purchase_orders_vendor ON purchase_orders(vendor_id);
CREATE INDEX idx_purchase_orders_status ON purchase_orders(status);

-- Marketing campaigns table
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    campaign_type ENUM('email', 'sms', 'push', 'social') NOT NULL,
    status ENUM('draft', 'scheduled', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    target_audience JSON,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Campaign recipients table
CREATE TABLE IF NOT EXISTS campaign_recipients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    customer_id INT NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'opened', 'clicked') DEFAULT 'pending',
    sent_at DATETIME,
    opened_at DATETIME,
    clicked_at DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Customer feedback table
CREATE TABLE IF NOT EXISTS customer_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Referral program table
CREATE TABLE IF NOT EXISTS referral_program (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    referrer_reward DECIMAL(10,2) NOT NULL,
    referee_reward DECIMAL(10,2) NOT NULL,
    minimum_purchase DECIMAL(10,2) DEFAULT 0.00,
    validity_days INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Referrals table
CREATE TABLE IF NOT EXISTS referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT NOT NULL,
    referrer_id INT NOT NULL,
    referee_id INT NOT NULL,
    status ENUM('pending', 'completed', 'expired', 'cancelled') DEFAULT 'pending',
    referrer_reward_status ENUM('pending', 'credited', 'expired') DEFAULT 'pending',
    referee_reward_status ENUM('pending', 'credited', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES referral_program(id),
    FOREIGN KEY (referrer_id) REFERENCES customers(id),
    FOREIGN KEY (referee_id) REFERENCES customers(id)
);

-- Push notifications table
CREATE TABLE IF NOT EXISTS push_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('promotion', 'reminder', 'announcement', 'system') NOT NULL,
    target_audience JSON,
    status ENUM('draft', 'scheduled', 'sent', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Notification recipients table
CREATE TABLE IF NOT EXISTS notification_recipients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id INT NOT NULL,
    customer_id INT NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending',
    sent_at DATETIME,
    delivered_at DATETIME,
    read_at DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES push_notifications(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Social media integration table
CREATE TABLE IF NOT EXISTS social_integration (
    id INT PRIMARY KEY AUTO_INCREMENT,
    platform ENUM('whatsapp', 'facebook', 'instagram', 'twitter') NOT NULL,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at DATETIME,
    status ENUM('active', 'inactive') DEFAULT 'inactive',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Social media posts table
CREATE TABLE IF NOT EXISTS social_media_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    platform ENUM('facebook', 'instagram', 'twitter', 'whatsapp') NOT NULL,
    content TEXT NOT NULL,
    media_url VARCHAR(255),
    scheduled_time DATETIME,
    status ENUM('draft', 'scheduled', 'published', 'failed') DEFAULT 'draft',
    post_id VARCHAR(100),
    engagement_metrics JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Email templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables JSON,
    category ENUM('promotion', 'reminder', 'welcome', 'follow-up', 'custom') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Campaign analytics table
CREATE TABLE IF NOT EXISTS campaign_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    metric_type ENUM('open_rate', 'click_rate', 'conversion_rate', 'bounce_rate', 'unsubscribe_rate') NOT NULL,
    metric_value DECIMAL(5,2) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE
);

-- Social media analytics table
CREATE TABLE IF NOT EXISTS social_media_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    platform ENUM('facebook', 'instagram', 'twitter', 'whatsapp') NOT NULL,
    metric_type ENUM('impressions', 'reach', 'engagement', 'clicks', 'shares') NOT NULL,
    metric_value INT NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES social_media_posts(id) ON DELETE CASCADE
);

-- Insert sample data for referral program
INSERT INTO referral_program (name, description, referrer_reward, referee_reward, minimum_purchase, validity_days) VALUES
('Welcome Friends', 'Refer your friends and both get rewards!', 20.00, 20.00, 50.00, 30);

-- Insert sample email templates
INSERT INTO email_templates (name, subject, body, category, variables) VALUES
('Welcome Email', 'Welcome to Our Salon!', 'Dear {{customer_name}},\n\nWelcome to our salon! We''re excited to have you as a customer.\n\nBest regards,\nSalon Team', 'welcome', '["customer_name"]'),
('Appointment Reminder', 'Your Upcoming Appointment', 'Dear {{customer_name}},\n\nThis is a reminder of your appointment on {{appointment_date}} at {{appointment_time}}.\n\nBest regards,\nSalon Team', 'reminder', '["customer_name", "appointment_date", "appointment_time"]'),
('Special Offer', 'Special Offer Just for You!', 'Dear {{customer_name}},\n\nWe have a special offer for you: {{offer_details}}\n\nBest regards,\nSalon Team', 'promotion', '["customer_name", "offer_details"]');

-- Insert sample social media posts
INSERT INTO social_media_posts (platform, content, scheduled_time, status) VALUES
('facebook', 'Check out our new summer collection! #SummerStyle #SalonLife', DATE_ADD(NOW(), INTERVAL 1 DAY), 'scheduled'),
('instagram', 'Before and after transformation! #HairTransformation #SalonLife', DATE_ADD(NOW(), INTERVAL 2 DAY), 'scheduled'),
('twitter', 'Book your appointment now and get 20% off! #SalonDeals', DATE_ADD(NOW(), INTERVAL 3 DAY), 'scheduled');

-- Create indexes for better performance
CREATE INDEX idx_campaigns_status ON marketing_campaigns(status);
CREATE INDEX idx_campaigns_dates ON marketing_campaigns(start_date, end_date);
CREATE INDEX idx_feedback_rating ON customer_feedback(rating);
CREATE INDEX idx_referrals_status ON referrals(status);
CREATE INDEX idx_notifications_status ON push_notifications(status);
CREATE INDEX idx_notification_recipients_status ON notification_recipients(status);
CREATE INDEX idx_social_integration_platform ON social_integration(platform);
CREATE INDEX idx_social_media_posts_platform ON social_media_posts(platform);
CREATE INDEX idx_social_media_posts_status ON social_media_posts(status);
CREATE INDEX idx_email_templates_category ON email_templates(category);
CREATE INDEX idx_campaign_analytics_campaign ON campaign_analytics(campaign_id);
CREATE INDEX idx_social_media_analytics_post ON social_media_analytics(post_id); 