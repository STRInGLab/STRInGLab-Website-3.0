CREATE TABLE u758484694_stringpricing.Pricing (
    ID int NOT NULL,
    Task varchar(255) NOT NULL,
    Qty int,
    Hours int,
    Charges int,
    Calculation int,
    Country varchar(50),
    PRIMARY KEY (ID)
);


CREATE TABLE services (
    id INTEGER PRIMARY KEY,
    task TEXT NOT NULL,
    hours INTEGER NOT NULL,
    charges INTEGER NOT NULL
);
INSERT INTO services (task, hours, charges) VALUES
('Website {5 Pages}', 10, 500),
('Extra Pages', 2, 100),
('Functions {Eg. Contact form PHP, Filters, etc.}', 5, 150),
('Dynamic Components', 8, 400),
('API Development {If Dynamic Components chosen}', 15, 600),
('DataBase Architecture & Development', 12, 550),
('Task', 4, 80);


CREATE TABLE Invoice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoiceTo VARCHAR(255) NOT NULL,
    invoiceName VARCHAR(255) NOT NULL,
    invoiceNo VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    paymentInfo TEXT NOT NULL,
    terms TEXT NOT NULL,
    invoiceOrQuote VARCHAR(50) NOT NULL
);

ALTER TABLE Invoice
ADD advancePaid DECIMAL(10,2) NOT NULL,
ADD taxType VARCHAR(50) NOT NULL;

CREATE TABLE InvoiceData (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoiceId INT,
    description TEXT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoiceId) REFERENCES Invoice(id)
);


CREATE TABLE Services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE ServiceTasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT,
    task_name VARCHAR(255) NOT NULL,
    hrs INT,
    FOREIGN KEY (service_id) REFERENCES Services(id)
);

CREATE TABLE CountryPricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT,
    country_code CHAR(2),
    charges DECIMAL(10, 2) NOT NULL,
    currency_symbol VARCHAR(10),
    FOREIGN KEY (task_id) REFERENCES ServiceTasks(id)
);

INSERT INTO Services (name) VALUES 
('Website'),
('PWA'),
('Design');

INSERT INTO ServiceTasks (service_id, task_name, hrs) VALUES 
(1, 'Web Task 1', 2),
(1, 'Web Task 2', 3),
(2, 'PWA Task 1', 2),
(2, 'PWA Task 2', 3),
(3, 'Design Task 1', 2),
(3, 'Design Task 2', 3);

-- Pricing for US
INSERT INTO CountryPricing (task_id, country_code, charges, currency_symbol) VALUES 
(1, 'US', 100, '$'),
(2, 'US', 150, '$'),
(3, 'US', 120, '$'),
(4, 'US', 140, '$'),
(5, 'US', 110, '$'),
(6, 'US', 160, '$');

-- Pricing for India
INSERT INTO CountryPricing (task_id, country_code, charges, currency_symbol) VALUES 
(1, 'IN', 7000, '₹'),
(2, 'IN', 10500, '₹'),
(3, 'IN', 8400, '₹'),
(4, 'IN', 9800, '₹'),
(5, 'IN', 7700, '₹'),
(6, 'IN', 11200, '₹');

-- Pricing for UK
INSERT INTO CountryPricing (task_id, country_code, charges, currency_symbol) VALUES 
(1, 'UK', 80, '£'),
(2, 'UK', 120, '£'),
(3, 'UK', 95, '£'),
(4, 'UK', 110, '£'),
(5, 'UK', 85, '£'),
(6, 'UK', 125, '£');
