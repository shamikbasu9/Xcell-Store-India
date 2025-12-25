-- Add media_type column to product_images table
ALTER TABLE product_images 
ADD COLUMN media_type ENUM('image', 'video') NOT NULL DEFAULT 'image' AFTER image_path;

-- Update existing records to have 'image' as the default media type
UPDATE product_images SET media_type = 'image' WHERE media_type IS NULL;
