# 🎵 YouTube to MP3 Converter

![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)
![Status](https://img.shields.io/badge/Status-Beta-yellow)
![License](https://img.shields.io/badge/License-MIT-green)

A powerful YouTube to MP3 converter that supports both YouTube and YouTube Music, including playlist functionality. This project is currently in **BETA** stage.

## ✨ Features

- 🎵 Convert YouTube videos to MP3
- 📑 Playlist support
- 🎧 YouTube Music support
- 📊 Real-time progress tracking
- 🎯 Clean and modern UI
- 🔄 Automatic file cleanup

## 🚀 Prerequisites

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- [yt-dlp](https://github.com/yt-dlp/yt-dlp) - YouTube video downloader
- [FFmpeg](https://ffmpeg.org/) - Media converter

## 💻 Installation

### 1. Clone the repository
```bash
git clone https://github.com/darkwob/youtube-mp3-converter.git
cd youtube-mp3-converter
```

### 2. Install Dependencies

#### Windows
1. Create a `bin` directory in the project root
2. Download [yt-dlp.exe](https://github.com/yt-dlp/yt-dlp/releases) and place it in the `bin` folder
3. Download [FFmpeg](https://www.gyan.dev/ffmpeg/builds/) (ffmpeg.exe, ffprobe.exe) and place them in the `bin` folder

#### Linux
```bash
# Install yt-dlp
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
sudo chmod a+rx /usr/local/bin/yt-dlp

# Install FFmpeg
sudo apt update
sudo apt install ffmpeg
```

#### macOS
```bash
# Using Homebrew
brew install yt-dlp
brew install ffmpeg
```

### 3. Configure Web Server

Make sure your web server has write permissions for these directories:
- `downloads/`
- `temp/`
- `progress/`

### 4. PHP Configuration

Modify your `php.ini`:
```ini
max_execution_time = 36000
memory_limit = 512M
```

## 🎯 Usage

1. Open the application in your web browser
2. Paste a YouTube or YouTube Music URL
3. Click "Convert"
4. Wait for the conversion to complete
5. Download your MP3 file

## ⚠️ Beta Notice

This project is currently in BETA stage. You may encounter:
- Occasional conversion failures
- Performance issues with large playlists
- UI/UX improvements needed
- Limited error handling in some cases

Please report any issues you find!

## 🔧 Troubleshooting

### Common Issues

1. **"yt-dlp not found" error**
   - Ensure yt-dlp is properly installed in the `bin` directory (Windows) or system-wide (Linux/macOS)
   - Check file permissions

2. **"FFmpeg not found" error**
   - Verify FFmpeg installation
   - Check if FFmpeg is in the system PATH (Linux/macOS) or in the `bin` directory (Windows)

3. **Timeout Issues**
   - Increase PHP timeout limits in php.ini
   - Check server configuration

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the Project
2. Create your Feature Branch
3. Commit your Changes
4. Push to the Branch
5. Open a Pull Request

## 📞 Support

If you encounter any issues or have questions, please open an issue on GitHub. 