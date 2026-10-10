-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 01:39 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medical_store_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `bill_no` int(11) NOT NULL,
  `bill_date` date NOT NULL,
  `customer_id` varchar(10) DEFAULT NULL,
  `pharmacist_id` varchar(10) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `payment_mode` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`bill_no`, `bill_date`, `customer_id`, `pharmacist_id`, `total_amount`, `payment_mode`, `status`) VALUES
(1, '2026-09-25', 'C001', 'P001', 85.00, 'Cash', 'Completed'),
(2, '2026-09-25', 'C002', 'P002', 120.00, 'UPI', 'Completed'),
(3, '2026-09-26', 'C003', 'P001', 150.00, 'Cash', 'Completed'),
(4, '2026-09-26', 'C003', NULL, 110.00, NULL, 'Completed'),
(5, '2026-09-26', 'C004', NULL, 60.00, NULL, 'Completed'),
(6, '2026-09-26', 'C003', NULL, 280.00, NULL, 'Completed'),
(7, '2026-09-26', 'C003', NULL, 280.00, NULL, 'Completed'),
(8, '2026-09-26', 'C003', NULL, 560.00, NULL, 'Completed'),
(9, '2026-09-26', 'C003', NULL, 90.00, NULL, 'Completed'),
(10, '2026-09-26', 'C003', NULL, 70.00, NULL, 'Completed'),
(11, '2026-09-27', 'C003', NULL, 70.00, NULL, 'Completed'),
(12, '2026-09-27', 'C003', NULL, 20.00, NULL, 'Completed'),
(13, '2026-09-27', 'C003', NULL, 130.00, NULL, 'Completed'),
(14, '2026-09-27', 'C003', NULL, 70.00, NULL, 'Completed'),
(15, '2026-09-27', 'C006', NULL, 30.00, NULL, 'Completed'),
(16, '2026-09-27', 'C007', NULL, 315.00, NULL, 'Completed'),
(17, '2026-09-28', 'C001', NULL, 180.00, NULL, 'Completed'),
(18, '2026-09-29', 'C009', NULL, 1450.00, NULL, 'Completed'),
(19, '2026-10-02', 'C010', NULL, 75.00, NULL, 'Completed'),
(20, '2026-10-03', 'C011', NULL, 175.00, NULL, 'Completed'),
(21, '2026-10-03', 'C012', NULL, 150.00, NULL, 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `bill_items`
--

CREATE TABLE `bill_items` (
  `bill_no` int(11) NOT NULL,
  `medicine_id` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_items`
--

INSERT INTO `bill_items` (`bill_no`, `medicine_id`, `quantity`, `amount`) VALUES
(1, 'M001', 2, 50.00),
(1, 'M006', 1, 20.00),
(1, 'M011', 1, 15.00),
(2, 'M003', 2, 70.00),
(2, 'M012', 1, 50.00),
(3, 'M019', 1, 150.00),
(4, 'M020', 2, 110.00),
(5, 'M011', 4, 60.00),
(6, 'M003', 8, 280.00),
(7, 'M003', 8, 280.00),
(8, 'M018', 7, 560.00),
(9, 'M015', 1, 90.00),
(10, 'M003', 2, 70.00),
(11, 'M003', 2, 70.00),
(12, 'M006', 1, 20.00),
(13, 'M017', 2, 130.00),
(14, 'M010', 2, 70.00),
(15, 'M002', 1, 30.00),
(16, 'M010', 9, 315.00),
(17, 'M002', 6, 180.00),
(18, 'M026', 1, 1450.00),
(19, 'M030', 1, 75.00),
(20, 'M003', 5, 175.00),
(21, 'M014', 6, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `Cust_ID` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `customer_type` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`Cust_ID`, `name`, `phone`, `address`, `customer_type`) VALUES
('', 'vipin joshi', '9122245670', NULL, NULL),
('C001', 'Rahul Sharma', '9000000001', 'Pune', 'Regular'),
('C002', 'Priya Patil', '9000000002', 'Pune', 'Regular'),
('C003', 'Amit Shah', '9000000003', 'Mumbai', 'Regular'),
('C004', 'Neha Joshi', '9000000004', 'Pune', 'Regular'),
('C005', 'Rohan Mehta', '9000000005', 'Nashik', 'Regular'),
('C006', 'Anand Mukhija', '9080706059', NULL, NULL),
('C007', 'Abhinav Gandhi', '9080706050', NULL, NULL),
('C008', 'Vinay Chauhan', '9988786655', NULL, NULL),
('C009', 'ABC', '9900', NULL, NULL),
('C010', 'xyz', '9876654321', NULL, NULL),
('C011', 'rutuja', '07709438325', NULL, NULL),
('C012', 'rakesh', '09970274118', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `Med_ID` varchar(10) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `Batch_No` varchar(30) DEFAULT NULL,
  `mfg_date` date DEFAULT NULL,
  `Expiry_Date` date DEFAULT NULL,
  `Quantity` int(11) DEFAULT 0,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Supp_ID` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`Med_ID`, `Name`, `category`, `Batch_No`, `mfg_date`, `Expiry_Date`, `Quantity`, `cost_price`, `Price`, `Supp_ID`) VALUES
('M001', 'Paracetamol 500mg', 'Tablets & Capsules', 'PCM001', '2025-01-01', '2027-12-31', 100, 18.00, 25.00, 'S001'),
('M002', 'Crocin', 'Tablets & Capsules', 'CRO002', '2025-01-01', '2027-11-30', 73, 22.00, 30.00, 'S002'),
('M003', 'Dolo 650', 'Tablets & Capsules', 'DOL003', '2025-02-01', '2028-01-31', 125, 25.00, 35.00, 'S003'),
('M004', 'Azithromycin 500mg', 'Tablets & Capsules', 'AZI004', '2025-01-15', '2027-10-31', 60, 65.00, 85.00, 'S004'),
('M005', 'Amoxicillin 500mg', 'Tablets & Capsules', 'AMO005', '2025-02-15', '2027-09-30', 70, 55.00, 75.00, 'S005'),
('M006', 'Cetirizine 10mg', 'Tablets & Capsules', 'CET006', '2025-03-01', '2028-02-28', 89, 14.00, 20.00, 'S006'),
('M007', 'Pantoprazole 40mg', 'Tablets & Capsules', 'PAN007', '2025-01-01', '2027-12-31', 65, 32.00, 45.00, 'S007'),
('M008', 'Omeprazole 20mg', 'Tablets & Capsules', 'OME008', '2025-03-01', '2028-03-31', 75, 28.00, 40.00, 'S008'),
('M009', 'Ibuprofen 400mg', 'Tablets & Capsules', 'IBU009', '2025-01-01', '2027-08-31', 85, 20.00, 30.00, 'S009'),
('M010', 'Diclofenac 50mg', 'Tablets & Capsules', 'DIC010', '2025-01-01', '2027-07-31', 44, 24.00, 35.00, 'S010'),
('M011', 'ORS Sachet', 'Personal Care', 'ORS011', '2025-01-01', '2027-12-31', 226, 10.00, 15.00, 'S001'),
('M012', 'Vitamin C', 'Tablets & Capsules', 'VIT012', '2025-02-01', '2028-04-30', 100, 35.00, 50.00, 'S002'),
('M013', 'Calcium Tablets', 'Tablets & Capsules', 'CAL013', '2025-02-01', '2028-01-31', 90, 42.00, 60.00, 'S003'),
('M014', 'Antacid Tablets', 'Tablets & Capsules', 'ANT014', '2025-01-01', '2027-11-30', 104, 18.00, 25.00, 'S004'),
('M015', 'Cough Syrup', 'Syrups & Drops', 'COU015', '2025-01-15', '2027-10-31', 49, 65.00, 90.00, 'S005'),
('M016', 'Multivitamin', 'Tablets & Capsules', 'MUL016', '2025-03-01', '2028-05-31', 70, 85.00, 120.00, 'S006'),
('M017', 'Eye Drops', 'Syrups & Drops', 'EYE017', '2025-02-01', '2027-09-30', 43, 45.00, 65.00, 'S007'),
('M018', 'Pain Relief Gel', 'Personal Care', 'PAI018', '2025-01-01', '2028-02-28', 53, 55.00, 80.00, 'S008'),
('M019', 'Thermometer', 'Devices & Monitors', 'THE019', '2025-01-01', '2029-12-31', 30, 110.00, 150.00, 'S009'),
('M020', 'Hand Sanitizer', 'Personal Care', 'SAN020', '2025-01-01', '2028-06-30', 98, 40.00, 55.00, 'S010'),
('M021', 'Band aids', 'Personal Care', 'CR5401', NULL, '2034-10-26', 560, NULL, 5.00, NULL),
('M022', 'Baby Diapers (Size M)', 'Baby & Mother Care', 'BATCH-B22', NULL, '2028-03-31', 100, NULL, 350.00, NULL),
('M023', 'Baby Powder', 'Baby & Mother Care', 'BATCH-B23', NULL, '2028-01-31', 80, NULL, 120.00, NULL),
('M024', 'Gripe Water', 'Baby & Mother Care', 'BATCH-B24', NULL, '2027-11-30', 60, NULL, 95.00, NULL),
('M025', 'Prenatal Vitamins', 'Baby & Mother Care', 'BATCH-B25', NULL, '2027-08-31', 70, NULL, 180.00, NULL),
('M026', 'Digital BP Monitor', 'Devices & Monitors', 'BATCH-D26', NULL, '2029-01-01', 24, NULL, 1450.00, NULL),
('M027', 'Pulse Oximeter', 'Devices & Monitors', 'BATCH-D27', NULL, '2029-01-01', 40, NULL, 650.00, NULL),
('M028', 'Glucometer', 'Devices & Monitors', 'BATCH-D28', NULL, '2029-01-01', 30, NULL, 899.00, NULL),
('M029', 'Paracetamol Syrup (Pediatric)', 'Syrups & Drops', 'BATCH-S29', NULL, '2027-12-31', 90, NULL, 65.00, NULL),
('M030', 'Ear Drops', 'Syrups & Drops', 'BATCH-S30', NULL, '2027-10-31', 49, NULL, 75.00, NULL),
('M031', 'Multivitamin Syrup', 'Syrups & Drops', 'BATCH-S31', NULL, '2027-09-30', 60, NULL, 140.00, NULL),
('M032', 'ORS', 'Personal Care', 'CR5403', NULL, '2024-01-01', 20, NULL, 10.00, NULL),
('M033', 'ORS', 'Personal Care', 'CR5403', NULL, '2024-01-01', 20, NULL, 10.00, NULL),
('M034', 'Masks', 'Personal Care', 'CR5331', NULL, '2024-06-01', 90, NULL, 10.00, NULL),
('M035', 'diapers', 'Baby & Mother Care', 'VIT098', NULL, '2026-10-01', 200, NULL, 50.00, NULL),
('M036', 'ORS', 'Syrups & Drops', 'VIT098', NULL, '2026-10-01', 4, NULL, 44.00, NULL),
('M037', 'nicip', 'Tablets & Capsules', 'VIT111', NULL, '2023-01-17', 4, NULL, 44.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist`
--

CREATE TABLE `pharmacist` (
  `pharmacist_id` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `shift` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacist`
--

INSERT INTO `pharmacist` (`pharmacist_id`, `name`, `phone`, `address`, `qualification`, `shift`) VALUES
('P001', 'Dr. Anjali Deshmukh', '9100000001', 'Pune', 'B.Pharm', 'Morning'),
('P002', 'Dr. Rohit Patil', '9100000002', 'Pune', 'B.Pharm', 'Evening'),
('P003', 'Dr. Sneha Kulkarni', '9100000003', 'Mumbai', 'D.Pharm', 'Morning');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `Supp_ID` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(15) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`Supp_ID`, `name`, `contact`, `address`, `contact_person`) VALUES
('S001', 'Apollo Distributors', '9876543210', 'Mumbai', 'Rajesh Kumar'),
('S002', 'MediCare Suppliers', '9876543211', 'Pune', 'Amit Patil'),
('S003', 'HealthPlus Pharma', '9876543212', 'Nashik', 'Suresh Shah'),
('S004', 'Wellness Distributors', '9876543213', 'Mumbai', 'Neha Joshi'),
('S005', 'LifeCare Pharma', '9876543214', 'Pune', 'Rohan Mehta'),
('S006', 'Prime Medicals', '9876543215', 'Thane', 'Vijay More'),
('S007', 'Global Pharma', '9876543216', 'Pune', 'Kiran Desai'),
('S008', 'CarePlus Suppliers', '9876543217', 'Mumbai', 'Pooja Sharma'),
('S009', 'Medico Distributors', '9876543218', 'Navi Mumbai', 'Anil Verma'),
('S010', 'Trust Pharma', '9876543219', 'Pune', 'Manish Gupta'),
('S011', 'Pharma Distributors', '9878685848', 'Nagpur', NULL),
('S012', 'Rutuja Maroti Dadge', '755479', 'SAI PARK , DIGHI', NULL),
('S013', 'vijay', '545956565695', 'lona', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$j37FyljuaSZkrrkuMjpbJuLz0876D3Ltn4VPjZqjyEjSvIeD9gp.y');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`bill_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `pharmacist_id` (`pharmacist_id`);

--
-- Indexes for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD PRIMARY KEY (`bill_no`,`medicine_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`Cust_ID`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`Med_ID`),
  ADD KEY `supplier_id` (`Supp_ID`);

--
-- Indexes for table `pharmacist`
--
ALTER TABLE `pharmacist`
  ADD PRIMARY KEY (`pharmacist_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`Supp_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `bill_no` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`Cust_ID`),
  ADD CONSTRAINT `bills_ibfk_2` FOREIGN KEY (`pharmacist_id`) REFERENCES `pharmacist` (`pharmacist_id`);

--
-- Constraints for table `bill_items`
--
ALTER TABLE `bill_items`
  ADD CONSTRAINT `bill_items_ibfk_1` FOREIGN KEY (`bill_no`) REFERENCES `bills` (`bill_no`),
  ADD CONSTRAINT `bill_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`Med_ID`);

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`Supp_ID`) REFERENCES `suppliers` (`Supp_ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
