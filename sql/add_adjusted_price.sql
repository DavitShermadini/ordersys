-- Allow admin to adjust order item prices after checkout
ALTER TABLE order_items ADD COLUMN adjusted_price DECIMAL(10,2) DEFAULT NULL AFTER price;
