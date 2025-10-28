# SecureDocs - Secure File Management System 🔐📁

A modern, secure file management system built with Laravel that provides enterprise-grade document storage, collaboration, and blockchain-backed permanent archiving.

## 🚀 Features

### Core File Management
- **📁 Hierarchical Folder Structure**: Organize files in nested folders with drag-and-drop support
- **📤 Advanced Upload System**: Multi-file uploads with progress tracking and resumable uploads
- **🔍 Smart Search**: Full-text search across all documents with AI-powered suggestions
- **🗂️ File Organization**: Automatic categorization using AI/ML algorithms
- **📋 Batch Operations**: Select multiple files for bulk actions (move, delete, share)

### Security & Authentication
- **🔐 Multi-Factor Authentication**: WebAuthn biometric authentication + TOTP
- **👥 Role-Based Access Control**: User, and Admin roles
- **📊 Activity Tracking**: Comprehensive audit logs for all user actions
- **🔔 Real-Time Notifications**: Email and in-app notifications for security events
- **🌍 Session Management**: Trusted devices and suspicious activity detection

### Premium Features
- **🤖 AI-Powered Features**: Automatic file categorization and content analysis
- **⛓️ Blockchain Storage**: Permanent archiving on Arweave
- **💎 Premium Subscriptions**: Enhanced storage limits and advanced features
- **🔒 Encrypted Storage**: End-to-end encryption for sensitive documents

### & Sharing
- **👥 File Sharing**: Share files and folders with granular permissions


### Administration
- **👨‍💼 Admin Dashboard**: User management, analytics, and system monitoring
- **📊 Usage Analytics**: Storage usage, activity reports, and performance metrics
- **⚙️ System Configuration**: Database schema visualization and API endpoints
- **🔧 Maintenance Tools**: Automated cleanup and optimization scripts

## 🛠️ Technology Stack

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: JavaScript ES6+, TailwindCSS, Livewire
- **Database**: PostgreSQL with Supabase
- **Authentication**: Laravel Jetstream + WebAuthn
- **Storage**: Local storage + Blockchain (Arweave)
- **AI/ML**: Custom categorization engine
- **Deployment**: Github + Cloudflare Tunnels

## 📋 Requirements

- PHP 8.2 or higher
- PostgreSQL 13+
- Node.js 18+ with npm
- Composer
- Supabase account (for production database)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd securedocs
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   # Edit .env with your database and service configurations
   php artisan key:generate
   ```

5. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build Assets**
   ```bash
   npm run build
   ```

7. **Start Development Servers**
   ```bash
   # Terminal 1: Laravel server
   php artisan serve

   # Terminal 2: Vite dev server
   npm run dev

   # Terminal 3: Queue worker (optional)
   php artisan queue:work
   ```

## 🔧 Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# Application
APP_NAME=SecureDocs
APP_ENV=local
APP_DEBUG=true

# Database (Supabase)
DB_CONNECTION=pgsql
DB_HOST=db.your-project.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-password

# Blockchain Storage
ARWEAVE_API_KEY=your-arweave-key
PINATA_API_KEY=your-pinata-key
FILECOIN_API_KEY=your-filecoin-key

# AI Features
OPENAI_API_KEY=your-openai-key

# Notifications
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

### Database Schema

The application uses a comprehensive PostgreSQL schema with the following main tables:
- `users` - User accounts and profiles
- `files` - File metadata and storage information
- `folders` - Hierarchical folder structure
- `user_sessions` - Authentication sessions
- `notifications` - User notifications
- `activity_logs` - Audit trail

View the complete schema documentation in `docs/schema/DATABASE_SCHEMA_FULL.md`.

## 📖 Usage

### User Registration & Authentication
1. Register a new account or login with existing credentials
2. Set up 2FA using WebAuthn (biometric) or TOTP app
3. Verify your email address for full access

### File Management
1. **Upload Files**: Click "Upload" or drag files to the upload area
2. **Create Folders**: Right-click in file area or use the "New Folder" button
3. **Organize Files**: Drag files between folders or use batch operations
4. **Search**: Use the search bar to find files by name or content

### Premium Features
1. **Upgrade to Premium**: Access subscription management in your profile
2. **AI Categorization**: Enable automatic file organization
3. **Blockchain Storage**: Archive important files permanently

### Administration (Admin Users Only)
1. Access admin dashboard at `/admin`
2. Manage users, view analytics, and configure system settings
3. Monitor activity logs and security events

## 🔒 Security Features

- **End-to-End Encryption**: Files encrypted before storage
- **Access Control**: Granular permissions for files and folders
- **Audit Logging**: All actions tracked with timestamps and user context
- **Suspicious Activity Detection**: Automated monitoring for security threats
- **Secure Authentication**: WebAuthn biometric authentication support
- **Session Security**: Automatic logout on suspicious activity

## 🌐 API Endpoints

The application provides a RESTful API for integrations:

- `GET /api/files` - List user files
- `POST /api/files` - Upload new file
- `GET /api/files/{id}` - Download file
- `PATCH /api/files/{id}` - Update file metadata
- `DELETE /api/files/{id}` - Delete file
- `POST /api/files/{id}/share` - Share file with permissions

View complete API documentation in the routes files.

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

Run specific test categories:
```bash
php artisan test --filter FileManagement
php artisan test --filter Authentication
```

## 🚀 Deployment

### Docker Deployment
```bash
docker-compose up -d
```

### Production Deployment
1. Configure production environment variables
2. Run database migrations
3. Build and optimize assets: `npm run build`
4. Set up web server (Nginx/Apache) with PHP-FPM
5. Configure SSL certificates
6. Set up background job processing

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes and add tests
4. Run the test suite: `php artisan test`
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- 📧 Email: support@securedocs.com
- 📖 Documentation: [docs/](docs/)
- 🐛 Bug Reports: [GitHub Issues](https://github.com/your-repo/issues)

## 🙏 Acknowledgments

- Laravel Framework - The foundation of this application
- Supabase - Database and real-time features
- Arweave/Pinata/Filecoin - Decentralized storage solutions
- WebAuthn - Modern authentication standard

---

**SecureDocs** - Where security meets simplicity in document management.
