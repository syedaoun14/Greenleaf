-- ================================================
-- GreenLeaf Smart Plant Nursery
-- Database: Microsoft SQL Server 2014
-- Owner: Rania
-- ================================================

CREATE DATABASE GreenLeafNursery;
GO

USE GreenLeafNursery;
GO

-- ================================================
-- TABLE: Customers
-- ================================================
CREATE TABLE Customers (
    CustomerID INT IDENTITY(1,1) PRIMARY KEY,
    FullName NVARCHAR(100) NOT NULL,
    Email NVARCHAR(150) UNIQUE NOT NULL,
    Phone NVARCHAR(20),
    Address NVARCHAR(250),
    City NVARCHAR(100),
    PasswordHash NVARCHAR(255) NOT NULL,
    ProfileImage NVARCHAR(255) DEFAULT 'default.jpg',
    CreatedAt DATETIME DEFAULT GETDATE(),
    IsActive BIT DEFAULT 1
);

-- ================================================
-- TABLE: Admins
-- ================================================
CREATE TABLE Admins (
    AdminID INT IDENTITY(1,1) PRIMARY KEY,
    FullName NVARCHAR(100) NOT NULL,
    Email NVARCHAR(150) UNIQUE NOT NULL,
    PasswordHash NVARCHAR(255) NOT NULL,
    Role NVARCHAR(50) DEFAULT 'admin',
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ================================================
-- TABLE: Consultants
-- ================================================
CREATE TABLE Consultants (
    ConsultantID INT IDENTITY(1,1) PRIMARY KEY,
    FullName NVARCHAR(100) NOT NULL,
    Email NVARCHAR(150) UNIQUE NOT NULL,
    Phone NVARCHAR(20),
    Specialization NVARCHAR(200),
    Bio NVARCHAR(500),
    ProfileImage NVARCHAR(255) DEFAULT 'consultant_default.jpg',
    PasswordHash NVARCHAR(255) NOT NULL,
    IsAvailable BIT DEFAULT 1,
    Rating DECIMAL(3,1) DEFAULT 5.0,
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ================================================
-- TABLE: PlantCategories
-- ================================================
CREATE TABLE PlantCategories (
    CategoryID INT IDENTITY(1,1) PRIMARY KEY,
    CategoryName NVARCHAR(100) NOT NULL,
    Description NVARCHAR(300),
    CategoryImage NVARCHAR(255),
    IsActive BIT DEFAULT 1
);

-- ================================================
-- TABLE: Plants
-- ================================================
CREATE TABLE Plants (
    PlantID INT IDENTITY(1,1) PRIMARY KEY,
    PlantName NVARCHAR(150) NOT NULL,
    CategoryID INT FOREIGN KEY REFERENCES PlantCategories(CategoryID),
    Description NVARCHAR(1000),
    Price DECIMAL(10,2) NOT NULL,
    OldPrice DECIMAL(10,2),
    StockQuantity INT DEFAULT 0,
    PlantImage NVARCHAR(255),
    CareLevel NVARCHAR(50),   -- Easy, Medium, Hard
    WaterNeeds NVARCHAR(50),  -- Low, Medium, High
    Sunlight NVARCHAR(50),    -- Full Sun, Partial, Shade
    IsFeatured BIT DEFAULT 0,
    IsActive BIT DEFAULT 1,
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ================================================
-- TABLE: Seeds (Gardening Supplies)
-- ================================================
CREATE TABLE GardenSupplies (
    SupplyID INT IDENTITY(1,1) PRIMARY KEY,
    SupplyName NVARCHAR(150) NOT NULL,
    SupplyType NVARCHAR(100),  -- Seeds, Fertilizer, Tools, Pots
    Description NVARCHAR(500),
    Price DECIMAL(10,2) NOT NULL,
    StockQuantity INT DEFAULT 0,
    SupplyImage NVARCHAR(255),
    IsActive BIT DEFAULT 1,
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ================================================
-- TABLE: Orders
-- ================================================
CREATE TABLE Orders (
    OrderID INT IDENTITY(1,1) PRIMARY KEY,
    CustomerID INT FOREIGN KEY REFERENCES Customers(CustomerID),
    OrderDate DATETIME DEFAULT GETDATE(),
    TotalAmount DECIMAL(10,2),
    DeliveryAddress NVARCHAR(300),
    Status NVARCHAR(50) DEFAULT 'Pending',
    -- Status: Pending, Confirmed, Shipped, Delivered, Cancelled
    PaymentMethod NVARCHAR(50),
    PaymentStatus NVARCHAR(50) DEFAULT 'Unpaid',
    Notes NVARCHAR(500)
);

-- ================================================
-- TABLE: OrderDetails
-- ================================================
CREATE TABLE OrderDetails (
    OrderDetailID INT IDENTITY(1,1) PRIMARY KEY,
    OrderID INT FOREIGN KEY REFERENCES Orders(OrderID),
    ItemType NVARCHAR(20),  -- 'Plant' or 'Supply'
    ItemID INT,
    ItemName NVARCHAR(150),
    Quantity INT NOT NULL,
    UnitPrice DECIMAL(10,2) NOT NULL,
    Subtotal AS (Quantity * UnitPrice)
);

-- ================================================
-- TABLE: Consultations
-- ================================================
CREATE TABLE Consultations (
    ConsultationID INT IDENTITY(1,1) PRIMARY KEY,
    CustomerID INT FOREIGN KEY REFERENCES Customers(CustomerID),
    ConsultantID INT FOREIGN KEY REFERENCES Consultants(ConsultantID),
    ConsultationDate DATE NOT NULL,
    TimeSlot NVARCHAR(50),
    ConsultationType NVARCHAR(150),
    CustomerQuery NVARCHAR(1000),
    Status NVARCHAR(50) DEFAULT 'Pending',
    -- Status: Pending, Confirmed, Completed, Cancelled
    ConsultantNotes NVARCHAR(1000),
    Recommendations NVARCHAR(1000),
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ================================================
-- TABLE: Reviews
-- ================================================
CREATE TABLE Reviews (
    ReviewID INT IDENTITY(1,1) PRIMARY KEY,
    CustomerID INT FOREIGN KEY REFERENCES Customers(CustomerID),
    PlantID INT FOREIGN KEY REFERENCES Plants(PlantID),
    Rating INT CHECK (Rating BETWEEN 1 AND 5),
    ReviewText NVARCHAR(500),
    CreatedAt DATETIME DEFAULT GETDATE()
);

-- ================================================
-- SAMPLE DATA
-- ================================================

-- Admin
INSERT INTO Admins (FullName, Email, PasswordHash, Role)
VALUES ('Rania Al-Farooq', 'rania@greenleaf.pk', '$2y$10$examplehashedpassword', 'superadmin');

-- Categories
INSERT INTO PlantCategories (CategoryName, Description, CategoryImage) VALUES
('Indoor Plants', 'Perfect for homes and offices', 'cat_indoor.jpg'),
('Flowering Plants', 'Beautiful blooming flowers', 'cat_flowers.jpg'),
('Outdoor Garden', 'Plants for gardens and landscapes', 'cat_outdoor.jpg'),
('Succulents & Cacti', 'Low maintenance desert plants', 'cat_succulents.jpg'),
('Herb Garden', 'Edible herbs for kitchen use', 'cat_herbs.jpg');

-- Consultants
INSERT INTO Consultants (FullName, Email, Phone, Specialization, Bio, PasswordHash) VALUES
('Rania Al-Farooq', 'rania@greenleaf.pk', '+92-300-1234567', 'Lead Horticulturist', 'Founder and lead plant expert with 12 years experience.', '$2y$10$examplehash1'),
('Dr. Hamid Shah', 'hamid@greenleaf.pk', '+92-321-9876543', 'Plant Pathologist', 'PhD in Plant Science, specializes in disease diagnosis.', '$2y$10$examplehash2'),
('Sara Malik', 'sara@greenleaf.pk', '+92-333-5556667', 'Indoor Plant Specialist', 'Expert in tropical and indoor plant care.', '$2y$10$examplehash3'),
('Ahmed Khan', 'ahmed@greenleaf.pk', '+92-345-1112223', 'Landscape Designer', '8 years in landscape architecture and garden design.', '$2y$10$examplehash4');

-- Plants
INSERT INTO Plants (PlantName, CategoryID, Description, Price, OldPrice, StockQuantity, PlantImage, CareLevel, WaterNeeds, Sunlight, IsFeatured) VALUES
('Peace Lily', 1, 'Elegant white blooms, air purifying, perfect for low light indoor spaces.', 850, 1100, 45, 'https://images.unsplash.com/photo-1487530811176-3780de880c2d?w=500&q=80', 'Easy', 'Medium', 'Shade', 1),
('Red Rose Bush', 2, 'Classic fragrant roses. Rich red blooms from spring to autumn.', 1200, 1600, 30, 'https://images.unsplash.com/photo-1455659817273-f96807779a8a?w=500&q=80', 'Medium', 'High', 'Full Sun', 1),
('Monstera Deliciosa', 1, 'Iconic tropical plant with dramatic split leaves. Statement piece for modern interiors.', 2500, NULL, 20, 'https://images.unsplash.com/photo-1502977249166-824b3a8a4d6d?w=500&q=80', 'Easy', 'Medium', 'Partial', 1),
('White Jasmine', 2, 'Intensely fragrant white flowers. Great for trellises and balconies.', 750, NULL, 60, 'https://images.unsplash.com/photo-1519378045-f2c63ea8429f?w=500&q=80', 'Medium', 'Medium', 'Full Sun', 1),
('Rainbow Tulips', 2, 'Vibrant spring bloomers in mixed colors. Perfect for garden beds.', 480, 680, 100, 'https://images.unsplash.com/photo-1444021465936-c6ca81d39b84?w=500&q=80', 'Easy', 'Medium', 'Full Sun', 1),
('Desert Cactus Set', 4, 'Set of 3 sculptural cacti. Zero maintenance, maximum visual impact.', 950, NULL, 35, 'https://images.unsplash.com/photo-1459156212016-c812468e2115?w=500&q=80', 'Easy', 'Low', 'Full Sun', 1),
('Snake Plant', 1, 'Nearly indestructible. Purifies air and thrives in neglect.', 680, NULL, 80, 'https://images.unsplash.com/photo-1583513702439-2e611c58e93d?w=500&q=80', 'Easy', 'Low', 'Shade', 0),
('Sunflower', 3, 'Cheerful giant yellow blooms. Grows up to 6 feet tall.', 350, NULL, 150, 'https://images.unsplash.com/photo-1470509037663-253afd7f0f51?w=500&q=80', 'Easy', 'Medium', 'Full Sun', 0);

-- Garden Supplies
INSERT INTO GardenSupplies (SupplyName, SupplyType, Description, Price, StockQuantity, SupplyImage) VALUES
('Wildflower Seed Mix', 'Seeds', 'Premium mixed wildflower seeds, 50g pack, 30+ varieties.', 320, 200, 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=500&q=80'),
('Herb Garden Kit', 'Seeds', '5 variety herb seed pack: basil, mint, coriander, parsley, thyme.', 580, 120, 'https://images.unsplash.com/photo-1466692476868-aef1dfb1e735?w=500&q=80'),
('Organic Compost', 'Fertilizer', 'Premium organic compost 2kg bag. Enriches soil naturally.', 720, 90, 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?w=500&q=80'),
('Terracotta Pot Set', 'Pots', 'Set of 4 handmade terracotta pots. Sizes 4'', 6'', 8'', 10''.', 1100, 55, 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=500&q=80'),
('Premium Potting Mix', 'Soil', 'Nutrient-rich potting soil 5kg bag. Suitable for all plants.', 890, 70, 'https://images.unsplash.com/photo-1459156212016-c812468e2115?w=500&q=80');
GO
