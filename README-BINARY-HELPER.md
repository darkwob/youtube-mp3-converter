# Binary Helper Function Documentation

This package provides a flexible binary management system that allows users to manually place executables (like `yt-dlp` and `ffmpeg`) in their project and optionally provide custom paths.

## 🎯 Key Features

- ✅ **Manual Binary Placement**: Users download and place executables themselves
- ✅ **No Auto-Download**: Package never downloads executables automatically
- ✅ **Custom Path Support**: Users can override default locations
- ✅ **Cross-Platform**: Handles Windows `.exe` extensions automatically
- ✅ **Flat Structure**: All binaries in `bin/` directory (no subfolders)
- ✅ **Ready for proc_open()**: Returns full paths ready for execution

## 📁 Expected Directory Structure

```
your-project/
├── composer.json
├── bin/                  # User creates this manually
│   ├── ffmpeg.exe       # Windows
│   ├── ffmpeg           # Linux/macOS
│   ├── yt-dlp.exe       # Windows
│   └── yt-dlp           # Linux/macOS
└── src/
```

## 🚀 Main Helper Function

### `getExecutablePath(string $binaryName, ?string $customPath = null): string`

This is the primary function that handles all binary location logic:

```php
use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;

// Automatic detection in bin/ directory
$ytdlpPath = PlatformDetector::getExecutablePath('yt-dlp');
// Returns: /path/to/project/bin/yt-dlp (Linux) or C:\path\to\project\bin\yt-dlp.exe (Windows)

// User provides custom path
$ffmpegPath = PlatformDetector::getExecutablePath('ffmpeg', '/usr/local/bin/ffmpeg');
// Returns: /usr/local/bin/ffmpeg (if it exists and is executable)

// User provides custom filename in bin/
$customYtDlp = PlatformDetector::getExecutablePath('yt-dlp', 'youtube-dl-custom.exe');
// Returns: /path/to/project/bin/youtube-dl-custom.exe
```

## 📖 Usage Examples

### Basic Usage - Automatic Detection

```php
<?php

use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;

try {
    // Look for binary automatically in bin/ directory
    $ytdlpPath = PlatformDetector::getExecutablePath('yt-dlp');
    echo "Found yt-dlp at: {$ytdlpPath}\n";
    
    // Use with proc_open
    $command = [$ytdlpPath, '--version'];
    $process = proc_open($command, [...], $pipes);
    
} catch (\RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    // Error includes installation instructions
}
```

### Custom Paths

```php
<?php

// Scenario 1: User has binary in different location
try {
    $ffmpeg = PlatformDetector::getExecutablePath('ffmpeg', '/opt/ffmpeg/bin/ffmpeg');
    echo "Using custom ffmpeg: {$ffmpeg}\n";
} catch (\RuntimeException $e) {
    echo "Custom path not found: " . $e->getMessage() . "\n";
}

// Scenario 2: User has renamed binary in bin/ directory
try {
    $ytdlp = PlatformDetector::getExecutablePath('yt-dlp', 'yt-dlp-v2023.exe');
    echo "Using custom filename: {$ytdlp}\n";
} catch (\RuntimeException $e) {
    echo "Custom filename not found\n";
}

// Scenario 3: User provides relative path
try {
    $tool = PlatformDetector::getExecutablePath('ffmpeg', '../tools/ffmpeg');
    echo "Using relative path: {$tool}\n";
} catch (\RuntimeException $e) {
    echo "Relative path not found\n";
}
```

### Convenience Methods

```php
<?php

// Check if binary exists
if (PlatformDetector::binaryExists('yt-dlp')) {
    echo "yt-dlp is available!\n";
}

if (PlatformDetector::binaryExists('ffmpeg', '/custom/path/ffmpeg')) {
    echo "Custom ffmpeg is available!\n";
}

// Execute binary directly
$result = PlatformDetector::executeBinary('yt-dlp', ['--version']);
if ($result['success']) {
    echo "Version: " . trim($result['output'][0]) . "\n";
    echo "Binary used: " . $result['binary_path'] . "\n";
}

// Create command array for proc_open
$command = PlatformDetector::createCommand('ffmpeg', ['-i', 'input.mp4', 'output.mp3']);
// Returns: ['/path/to/ffmpeg', '-i', 'input.mp4', 'output.mp3']
```

### Flexible User Functions

Create wrapper functions that allow users to optionally provide custom paths:

```php
<?php

function getYtDlpExecutable(?string $userPath = null): string {
    return PlatformDetector::getExecutablePath('yt-dlp', $userPath);
}

function getFfmpegExecutable(?string $userPath = null): string {
    return PlatformDetector::getExecutablePath('ffmpeg', $userPath);
}

// Users can call these functions flexibly:
$ytdlp1 = getYtDlpExecutable();                    // Auto detection
$ytdlp2 = getYtDlpExecutable('/my/custom/yt-dlp'); // Custom path
$ffmpeg1 = getFfmpegExecutable();                  // Auto detection
$ffmpeg2 = getFfmpegExecutable('ffmpeg-static');   // Custom filename in bin/
```

## 🔧 Installation Instructions

When binaries are not found, the helper provides detailed installation instructions:

```php
try {
    $path = PlatformDetector::getExecutablePath('yt-dlp');
} catch (\RuntimeException $e) {
    echo $e->getMessage();
    // Output:
    // Binary 'yt-dlp' not found at: /path/to/project/bin/yt-dlp
    // 
    // Installation instructions:
    // To install yt-dlp:
    // 1. Create bin directory: /path/to/project/bin
    // 2. Download from: https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp
    // 3. Extract and place the executable as: /path/to/project/bin/yt-dlp
    // 4. Make sure the file is executable (chmod +x on Unix systems)
}
```

## 🌐 Cross-Platform Behavior

The helper automatically handles platform differences:

| Platform | Binary Name | Expected File | Custom Path Support |
|----------|-------------|---------------|-------------------|
| Windows  | `yt-dlp`    | `yt-dlp.exe`  | ✅ Full paths, filenames |
| Linux    | `yt-dlp`    | `yt-dlp`      | ✅ Full paths, filenames |
| macOS    | `yt-dlp`    | `yt-dlp`      | ✅ Full paths, filenames |

```php
// On Windows, this looks for bin/yt-dlp.exe
$path = PlatformDetector::getExecutablePath('yt-dlp');

// On Linux/macOS, this looks for bin/yt-dlp
$path = PlatformDetector::getExecutablePath('yt-dlp');

// Custom paths work on all platforms
$path = PlatformDetector::getExecutablePath('yt-dlp', 'C:\\Tools\\yt-dlp.exe'); // Windows
$path = PlatformDetector::getExecutablePath('yt-dlp', '/usr/local/bin/yt-dlp'); // Linux/macOS
```

## ⚡ Real-World Integration

### With YouTubeConverter

```php
<?php

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;

// The converter automatically uses getExecutablePath() internally
$converter = new YouTubeConverter(
    outputPath: './downloads',
    tempPath: './temp',
    progress: new FileProgress('./progress')
);

// Binaries are found automatically in bin/
$result = $converter->processVideo('https://www.youtube.com/watch?v=VIDEO_ID');
```

### With Custom Paths (Advanced)

If you need to customize binary locations in YouTubeConverter, you can extend it:

```php
<?php

class CustomYouTubeConverter extends YouTubeConverter {
    private ?string $customYtDlpPath;
    private ?string $customFfmpegPath;
    
    public function __construct(
        string $outputPath,
        string $tempPath,
        ProgressInterface $progress,
        ?ConverterOptions $options = null,
        ?string $customYtDlpPath = null,
        ?string $customFfmpegPath = null
    ) {
        parent::__construct($outputPath, $tempPath, $progress, $options);
        $this->customYtDlpPath = $customYtDlpPath;
        $this->customFfmpegPath = $customFfmpegPath;
    }
    
    protected function getYtDlpPath(): string {
        return PlatformDetector::getExecutablePath('yt-dlp', $this->customYtDlpPath);
    }
    
    protected function getFfmpegPath(): string {
        return PlatformDetector::getExecutablePath('ffmpeg', $this->customFfmpegPath);
    }
}
```

## 🚨 Error Handling

The helper provides clear error messages:

```php
try {
    $path = PlatformDetector::getExecutablePath('nonexistent');
} catch (\RuntimeException $e) {
    // Handle specific error cases
    if (strpos($e->getMessage(), 'not found') !== false) {
        // Binary doesn't exist
    } elseif (strpos($e->getMessage(), 'not executable') !== false) {
        // Binary exists but permissions issue
    }
}
```

## 📋 Summary

- **Main Function**: `getExecutablePath($binaryName, $customPath = null)`
- **Auto Detection**: Pass `null` for `$customPath` (default)
- **Custom Paths**: Pass full path, relative path, or filename
- **Cross-Platform**: Handles `.exe` extensions automatically
- **User-Friendly**: Clear error messages with installation instructions
- **Ready to Use**: Returns paths ready for `proc_open()` or `shell_exec()`

This system gives users complete flexibility while maintaining simplicity for common use cases! 