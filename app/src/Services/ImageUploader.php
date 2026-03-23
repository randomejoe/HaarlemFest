<?php 
namespace App\Services;

class ImageUploader {
    public static function handleImageUpload(array $image): string {
        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        move_uploaded_file($image['tmp_name'], __DIR__ . '/../../public/images/' . $filename);
        return $filename;
    }
}
    
?>