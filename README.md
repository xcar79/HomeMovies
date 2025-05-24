![HomeMovies Logo](https://raw.githubusercontent.com/xcar79/HomeMovies/refs/heads/main/homemovieslogo.png)

# HomeMovies

**HomeMovies** is a simple PHP web interface to control VLC remotely on your local network.  
List, search and play your movie collection, control playback (play/pause, seek) and close VLC—all from your browser.

## Features

- Dynamic list of video files (`.mp4`, `.avi`, `.mkv`, `.mov`)
- Live search with “home” and “clear” buttons
- Play in fullscreen in a single VLC instance
- Play/Pause toggle and real-time progress bar with drag-to-seek
- Close VLC from the web UI
- Responsive, dark-theme design optimized for mobile

## Setup

1. Clone this repo into your web server’s document root:
   ```bash
   git clone https://github.com/xcar79/HomeMovies.git

2. Edit index.php and set your movie folder path:
   ```bash
   define('MOVIES_PATH', 'H:\\Peliculas');

3. Enable VLC’s Lua HTTP interface:
   - Open VLC → Tools > Preferences → bottom-left: Show Settings > All
   - Navigate to Interface > Main interfaces, check Web
   - In Interface > Main interfaces > Lua, set Lua HTTP password to vlc123 (or your choice)

