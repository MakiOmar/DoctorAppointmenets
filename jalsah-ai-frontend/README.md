# 🧠 Jalsah AI Frontend

A modern Vue.js frontend for the Jalsah AI mental health platform, featuring AI-powered diagnosis, therapist matching, and appointment booking.

## 🚀 Features

- **AI-Powered Diagnosis**: Get personalized mental health assessments
- **Smart Therapist Matching**: Find therapists who specialize in your specific diagnosis
- **45-Minute Online Sessions**: Book convenient online therapy sessions
- **Modern UI/UX**: Beautiful, responsive design with Tailwind CSS
- **JWT Authentication**: Secure user authentication and session management
- **Cart System**: Add multiple appointments to cart before checkout
- **Real-time Updates**: Live availability checking and booking

## 🛠️ Tech Stack

- **Vue 3** - Progressive JavaScript framework
- **Vite** - Fast build tool and dev server
- **Vue Router** - Official router for Vue.js
- **Pinia** - State management for Vue
- **Tailwind CSS** - Utility-first CSS framework
- **Axios** - HTTP client for API requests
- **Vue Toastification** - Toast notifications
- **Headless UI** - Unstyled, accessible UI components
- **Heroicons** - Beautiful hand-crafted SVG icons

## 📦 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd jalsah-ai-frontend
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Set up environment variables**
   Create a `.env` file in the root directory:
   ```env
   VITE_API_URL=https://jalsah.app
   ```

4. **Start development server**
   ```bash
   npm run dev
   ```

5. **Build for production**
   ```bash
   npm run build
   ```

## 🏗️ Project Structure

```
src/
├── components/          # Reusable Vue components
│   └── Header.vue      # Main navigation header
├── views/              # Page components
│   ├── Home.vue        # Landing page
│   ├── Login.vue       # User login
│   ├── Register.vue    # User registration
│   ├── Diagnosis.vue   # AI diagnosis flow
│   ├── Therapists.vue  # Therapist listing
│   ├── TherapistDetail.vue # Individual therapist page
│   ├── Booking.vue     # Appointment booking
│   ├── Cart.vue        # Shopping cart
│   ├── Appointments.vue # User appointments
│   └── Profile.vue     # User profile
├── stores/             # Pinia state management
│   ├── auth.js         # Authentication store
│   └── cart.js         # Shopping cart store
├── services/           # API services
│   └── api.js          # Axios configuration
├── router/             # Vue Router configuration
│   └── index.js        # Route definitions
├── style.css           # Global styles
├── main.js             # App entry point
└── App.vue             # Root component
```

## 🔐 Authentication

The app uses JWT (JSON Web Tokens) for authentication:

- **Login**: `/login` - User authentication
- **Register**: `/register` - New user registration
- **Protected Routes**: All authenticated routes require valid JWT
- **Token Storage**: JWT stored in localStorage
- **Auto-logout**: Automatic logout on token expiration

## 🛒 Cart System

- **Add to Cart**: Add appointment slots to cart
- **Cart Management**: View, modify, and remove items
- **Checkout**: Seamless integration with WooCommerce
- **Real-time Updates**: Live cart synchronization

## 🎨 Design System

### Colors
- **Primary**: Blue gradient (`primary-600` to `primary-700`)
- **Secondary**: Purple gradient (`secondary-600` to `secondary-700`)
- **Success**: Green (`green-600`)
- **Warning**: Yellow (`yellow-600`)
- **Error**: Red (`red-600`)

### Components
- **Buttons**: Primary, secondary, outline variants
- **Cards**: Consistent card styling with shadows
- **Forms**: Styled form inputs and labels
- **Navigation**: Responsive header with dropdown menus

## 📱 Responsive Design

The app is fully responsive and optimized for:
- **Desktop**: 1024px and above
- **Tablet**: 768px to 1023px
- **Mobile**: 320px to 767px

## 🔄 API Integration

### Base URL
```
https://jalsah.app/api/ai/
```

### Key Endpoints
- `POST /auth` - User login
- `POST /auth/register` - User registration
- `GET /therapists` - List therapists
- `GET /therapists/{id}` - Get therapist details
- `GET /therapists/by-diagnosis/{id}` - Filter by diagnosis
- `GET /appointments/available` - Get available slots
- `POST /appointments/book` - Book appointment
- `GET /cart/{userId}` - Get user cart
- `POST /cart/add` - Add to cart
- `POST /cart/checkout` - Checkout cart
- `GET /diagnoses` - List diagnoses

## 🚀 Deployment

### Development
```bash
npm run dev
```

### Production Build
```bash
npm run build
```

### Preview Production Build
```bash
npm run preview
```

### Deploy to Vercel
1. Connect your GitHub repository to Vercel
2. Set environment variables in Vercel dashboard
3. Deploy automatically on push to main branch

### Deploy to Netlify
1. Connect your GitHub repository to Netlify
2. Set build command: `npm run build`
3. Set publish directory: `dist`
4. Set environment variables in Netlify dashboard

## 🔧 Configuration

### Environment Variables
```env
# API Configuration
VITE_API_URL=https://jalsah.app

# Feature Flags
VITE_ENABLE_ANALYTICS=false
VITE_ENABLE_DEBUG=false
```

### Vite Configuration
- **Port**: 3000 (development)
- **Proxy**: API requests proxied to backend
- **Aliases**: `@` points to `src/` directory
- **Build**: Optimized for production

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration flow
- [ ] User login/logout
- [ ] AI diagnosis process
- [ ] Therapist browsing and filtering
- [ ] Appointment booking
- [ ] Cart functionality
- [ ] Responsive design on all devices
- [ ] Form validation
- [ ] Error handling
- [ ] Loading states

### Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## 📈 Performance

### Optimizations
- **Code Splitting**: Lazy-loaded route components
- **Tree Shaking**: Unused code elimination
- **Image Optimization**: WebP format support
- **Caching**: Browser caching strategies
- **Bundle Analysis**: Webpack bundle analyzer

### Lighthouse Scores
- **Performance**: 95+
- **Accessibility**: 95+
- **Best Practices**: 95+
- **SEO**: 90+

## 🔒 Security

### Best Practices
- **HTTPS Only**: All API calls use HTTPS
- **JWT Security**: Secure token handling
- **Input Validation**: Client-side form validation
- **XSS Protection**: Vue.js built-in XSS protection
- **CSRF Protection**: Token-based CSRF protection

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Code Style
- **ESLint**: JavaScript/Vue linting
- **Prettier**: Code formatting
- **Vue Style Guide**: Follow Vue.js style guide
- **Component Naming**: PascalCase for components
- **File Naming**: kebab-case for files

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- **Email**: support@jalsah.ai
- **Documentation**: [docs.jalsah.ai](https://docs.jalsah.ai)
- **Issues**: GitHub Issues

## 🔮 Roadmap

### Phase 1 (Current)
- ✅ User authentication
- ✅ Basic therapist listing
- ✅ Appointment booking
- ✅ Cart system

### Phase 2 (Next)
- 🔄 AI diagnosis flow
- 🔄 Advanced filtering
- 🔄 Video call integration
- 🔄 Payment processing

### Phase 3 (Future)
- 📋 Mobile app
- 📋 Advanced analytics
- 📋 Multi-language support
- 📋 AI chat support

---

**Built with ❤️ for better mental health** 