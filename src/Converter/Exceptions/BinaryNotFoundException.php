<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

/**
 * Exception thrown when required binary files are not found
 * 
 * @requires PHP >=8.4
 */
class BinaryNotFoundException extends ConverterException
{
    public static function ytDlpNotFound(string $searchPath = ''): self
    {
        $message = "yt-dlp binary not found";
        if (!empty($searchPath)) {
            $message .= " in path: $searchPath";
        }
        
        $message .= "\n\nInstallation instructions:\n";
        $message .= "1. Download yt-dlp from: https://github.com/yt-dlp/yt-dlp/releases\n";
        $message .= "2. For Windows: Download yt-dlp.exe and place it in the bin/ directory\n";
        $message .= "3. For Linux/Mac: Download yt-dlp and place it in the bin/ directory\n";
        $message .= "4. Ensure the binary has execute permissions (chmod +x yt-dlp on Linux/Mac)\n";
        $message .= "5. Alternative: Install via pip: pip install yt-dlp";
        
        return new self($message);
    }

    public static function ffmpegNotFound(string $searchPath = ''): self
    {
        $message = "ffmpeg binary not found";
        if (!empty($searchPath)) {
            $message .= " in path: $searchPath";
        }
        
        $message .= "\n\nInstallation instructions:\n";
        $message .= "1. Download ffmpeg from: https://ffmpeg.org/download.html\n";
        $message .= "2. For Windows: Download ffmpeg.exe and place it in the bin/ directory\n";
        $message .= "3. For Linux: sudo apt-get install ffmpeg (Ubuntu/Debian) or equivalent\n";
        $message .= "4. For Mac: brew install ffmpeg\n";
        $message .= "5. Ensure the binary has execute permissions\n";
        $message .= "6. Alternative: Add ffmpeg to your system PATH";
        
        return new self($message);
    }

    public static function customBinaryNotFound(string $binaryName, string $path): self
    {
        $message = "Custom binary '$binaryName' not found at path: $path\n\n";
        $message .= "Please ensure:\n";
        $message .= "1. The file exists at the specified path\n";
        $message .= "2. The file has execute permissions\n";
        $message .= "3. The path is correct and accessible\n";
        $message .= "4. On Windows, ensure the .exe extension is included if needed";
        
        return new self($message);
    }

    public static function binaryNotExecutable(string $binaryPath): self
    {
        $message = "Binary found but not executable: $binaryPath\n\n";
        $message .= "Fix instructions:\n";
        $message .= "1. On Linux/Mac: chmod +x $binaryPath\n";
        $message .= "2. On Windows: Check file properties and ensure it's not blocked\n";
        $message .= "3. Verify the file is not corrupted\n";
        $message .= "4. Check antivirus software hasn't quarantined the file";
        
        return new self($message);
    }

    public static function systemBinaryNotFound(string $binaryName): self
    {
        $message = "System binary '$binaryName' not found in PATH\n\n";
        $message .= "Installation options:\n";
        $message .= "1. Install $binaryName system-wide and add to PATH\n";
        $message .= "2. Place the binary in the project's bin/ directory\n";
        $message .= "3. Specify a custom path in the converter options";
        
        return new self($message);
    }
}