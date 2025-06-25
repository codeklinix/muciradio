# MuciRadio - Multi Radio Streaming Platform

A modern web-based radio streaming platform that allows users to discover and listen to radio stations from around the world.

## Features

- 🎵 Stream radio stations worldwide
- 🎨 Modern, responsive design
- 📱 Progressive Web App (PWA) support
- ❤️ Favorite stations
- 🎯 Genre filtering
- 🔍 Search functionality
- 🎛️ Admin panel for station management
- 📻 Embed players for websites
- 💎 Premium features

## Quick Deploy

### Deploy to Railway (Recommended)

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/template/7qT9xE?referralCode=alphasec)

**After deployment:**
1. Add a PostgreSQL database from Railway dashboard
2. Your app will be available at the generated Railway URL

### Manual Deployment

1. Clone this repository
2. Set up your database (MySQL or PostgreSQL)
3. Configure environment variables
4. Deploy to your hosting platform

## Environment Variables

- `DATABASE_URL` - PostgreSQL connection string (for Railway)
- `DB_HOST` - MySQL host (for manual setup)
- `DB_NAME` - Database name
- `DB_USER` - Database username  
- `DB_PASS` - Database password

## Local Development

1. Clone the repository
2. Set up XAMPP or similar local server
3. Import the database structure
4. Access via `http://localhost/muciradio`

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP
- **Database:** MySQL/PostgreSQL
- **Audio:** Web Audio API
- **PWA:** Service Workers, Web App Manifest

## License

MIT License - feel free to use for personal and commercial projects.
