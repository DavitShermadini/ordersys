SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL AFTER unit;
ALTER TABLE products ADD CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

INSERT INTO categories (name) VALUES
('საოფისე ავეჯი'),
('საოფისე ტექნიკა'),
('პური და საცხობი'),
('რძის პროდუქტები'),
('ხორცი'),
('ბოსტნეული'),
('ხილი'),
('კონსერვი'),
('სოუსი და ზეთი'),
('გაზიანი სასმელები'),
('ცხელი სასმელები'),
('ბურღულეული'),
('კვერცხი');

UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'საოფისე ავეჯი')
    WHERE name IN ('Office Chair','Standing Desk');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'საოფისე ტექნიკა')
    WHERE name IN ('Laptop Stand','Wireless Mouse','Mechanical Keyboard','Monitor 27"','USB-C Hub 7-in-1','Webcam HD 1080p','Desk Lamp LED','Cable Organiser Kit');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'პური და საცხობი')
    WHERE name IN ('შოთის პური','ლავაში');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'რძის პროდუქტები')
    WHERE name IN ('სულგუნი','ბრინძა','მაწოვანი','კარაქი','რძე');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'ხორცი')
    WHERE name IN ('საქონლის ხორცი','ქათმის ფილე','ღორის კარკარა','ბარამული');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'ბოსტნეული')
    WHERE name IN ('პომიდორი','კიტრი','კარტოფილი','ხახვი','ნიორი','წიწაკა');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'ხილი')
    WHERE name IN ('ვაშლი','ყურძენი','ბანანი','ფორთოხალი');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'კონსერვი')
    WHERE name IN ('ლობიო კონსერვი','სიმინდის კონსერვი');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'სოუსი და ზეთი')
    WHERE name IN ('კეტჩუპი','მაიონეზი','მზესუმზირის ზეთი');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'გაზიანი სასმელები')
    WHERE name IN ('ბორჯომი','ნატახტარი ლიმონათი');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'ცხელი სასმელები')
    WHERE name IN ('შავი ჩაი','ხსნადი ყავა');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'ბურღულეული')
    WHERE name IN ('შაქარი','ფქვილი','ბრინჯი','მარილი');
UPDATE products SET category_id = (SELECT id FROM categories WHERE name = 'კვერცხი')
    WHERE name = 'კვერცხი';
