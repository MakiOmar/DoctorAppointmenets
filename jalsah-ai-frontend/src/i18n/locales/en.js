export default {
  // Common
  common: {
    loading: 'Loading...',
    error: 'An error occurred',
    success: 'Success',
    cancel: 'Cancel',
    save: 'Save',
    edit: 'Edit',
    delete: 'Delete',
    confirm: 'Confirm',
    back: 'Back',
    next: 'Next',
    previous: 'Previous',
    submit: 'Submit',
    search: 'Search',
    filter: 'Filter',
    sort: 'Sort',
    view: 'View',
    add: 'Add',
    remove: 'Remove',
    close: 'Close',
    yes: 'Yes',
    no: 'No',
    ok: 'OK',
    retry: 'Retry',
    refresh: 'Refresh',
    download: 'Download',
    upload: 'Upload',
    select: 'Select',
    all: 'All',
    none: 'None',
    required: 'Required',
    optional: 'Optional',
    unknown: 'Unknown',
    na: 'N/A'
  },

  // Navigation
  nav: {
    home: 'Home',
    therapists: 'Therapists',
    diagnosis: 'Diagnosis',
    appointments: 'Appointments',
    profile: 'Profile',
    cart: 'Cart',
    login: 'Login',
    register: 'Register',
    logout: 'Logout',
    language: 'Language'
  },

  // Home Page
  home: {
    hero: {
      title: 'Smart & Advanced Mental Health Care',
      subtitle: 'Get AI-powered diagnosis and find the right therapist for you',
      cta: 'Start AI Diagnosis',
      secondaryCta: 'Browse Therapists'
    },
    features: {
      title: 'Why Choose Jalsah AI?',
      aiDiagnosis: {
        title: 'AI Diagnosis',
        description: 'Get accurate diagnosis using artificial intelligence'
      },
      expertTherapists: {
        title: 'Expert Therapists',
        description: 'Licensed and qualified mental health professionals'
      },
      onlineSessions: {
        title: 'Online Sessions',
        description: 'Safe and convenient sessions from your home'
      },
      flexibleScheduling: {
        title: 'Flexible Scheduling',
        description: 'Book appointments that fit your schedule'
      }
    },
    howItWorks: {
      title: 'How It Works',
      step1: {
        title: 'AI Diagnosis',
        description: 'Complete a short questionnaire for personalized diagnosis'
      },
      step2: {
        title: 'Choose Therapist',
        description: 'Select from matched therapists based on your needs'
      },
      step3: {
        title: 'Book Session',
        description: 'Book your session and pay securely'
      },
      step4: {
        title: 'Start Therapy',
        description: 'Join your session online and begin your healing journey'
      }
    }
  },

  // Authentication
  auth: {
    login: {
      title: 'Login',
      email: 'Email',
      password: 'Password',
      rememberMe: 'Remember me',
      forgotPassword: 'Forgot password?',
      noAccount: "Don't have an account?",
      signUp: 'Sign up',
      submit: 'Login',
      errors: {
        invalidCredentials: 'Invalid credentials',
        emailRequired: 'Email is required',
        passwordRequired: 'Password is required'
      }
    },
    register: {
      title: 'Create New Account',
      firstName: 'First Name',
      lastName: 'Last Name',
      email: 'Email',
      phone: 'Phone Number',
      password: 'Password',
      confirmPassword: 'Confirm Password',
      agreeTerms: 'I agree to the terms and conditions',
      submit: 'Create Account',
      haveAccount: 'Already have an account?',
      signIn: 'Sign in',
      errors: {
        passwordMismatch: 'Passwords do not match',
        emailExists: 'Email already exists',
        weakPassword: 'Password is too weak'
      }
    }
  },

  // Diagnosis
  diagnosis: {
    title: 'AI-Powered Mental Health Diagnosis',
    subtitle: 'Get a personalized assessment to help you find the right therapist',
    step1: {
      title: 'Step 1: Tell us about yourself',
      mood: 'How would you describe your current mood?',
      moodOptions: {
        happy: 'Happy and content',
        neutral: 'Neutral',
        sad: 'Sad or depressed',
        anxious: 'Anxious or worried',
        angry: 'Angry or irritable',
        stressed: 'Stressed or overwhelmed'
      },
      duration: 'How long have you been feeling this way?',
      durationOptions: {
        less_than_week: 'Less than a week',
        few_weeks: 'A few weeks',
        few_months: 'A few months',
        six_months: '6 months or more',
        year_plus: 'Over a year'
      }
    },
    step2: {
      title: 'Step 2: What symptoms are you experiencing?',
      symptoms: {
        anxiety: 'Anxiety or excessive worry',
        depression: 'Depression or low mood',
        stress: 'High stress levels',
        sleep: 'Sleep problems',
        appetite: 'Changes in appetite',
        energy: 'Low energy or fatigue',
        concentration: 'Difficulty concentrating',
        irritability: 'Irritability or anger',
        isolation: 'Social withdrawal',
        hopelessness: 'Feelings of hopelessness',
        panic: 'Panic attacks',
        trauma: 'Trauma-related symptoms'
      }
    },
    step3: {
      title: 'Step 3: How is this affecting your life?',
      impact: 'How much are these symptoms affecting your daily life?',
      impactOptions: {
        minimal: 'Minimal impact',
        mild: 'Mild impact',
        moderate: 'Moderate impact',
        severe: 'Severe impact',
        extreme: 'Extreme impact'
      },
      affectedAreas: 'Which areas of your life are most affected?',
      areas: {
        work: 'Work or career',
        relationships: 'Relationships',
        family: 'Family life',
        social: 'Social life',
        health: 'Physical health',
        finances: 'Financial situation',
        hobbies: 'Hobbies and interests',
        daily_routine: 'Daily routine'
      }
    },
    step4: {
      title: 'Step 4: What are your goals for therapy?',
      goals: 'What would you like to achieve through therapy?',
      goalsPlaceholder: 'Describe your goals and what you hope to accomplish...',
      preferredApproach: 'What type of therapy approach interests you most?',
      approachOptions: {
        none: 'No preference',
        cbt: 'Cognitive Behavioral Therapy (CBT)',
        psychodynamic: 'Psychodynamic Therapy',
        humanistic: 'Humanistic Therapy',
        mindfulness: 'Mindfulness-Based Therapy',
        solution_focused: 'Solution-Focused Therapy'
      }
    },
    progress: 'Step {step} of 4',
    complete: '{percent}% Complete',
    submit: 'Get My Diagnosis',
    analyzing: 'Analyzing...',
    success: 'Diagnosis completed! Finding therapists for you...'
  },

  // Therapists
  therapists: {
    title: 'Find Your Perfect Therapist',
    subtitle: 'Browse our qualified therapists and find the one that\'s right for you',
    filters: {
      specialization: 'Specialization',
      allSpecializations: 'All specializations',
      priceRange: 'Price Range',
      anyPrice: 'Any price',
      availability: 'Availability',
      anyTime: 'Any time',
      sortBy: 'Sort By',
      highestRated: 'Highest Rated',
      lowestPrice: 'Lowest Price',
      highestPrice: 'Highest Price',
      mostAvailable: 'Most Available'
    },
    specializations: {
      anxiety: 'Anxiety Disorders',
      depression: 'Depression',
      stress: 'Stress Management',
      relationships: 'Relationship Issues',
      trauma: 'Trauma and PTSD',
      addiction: 'Addiction',
      eating: 'Eating Disorders',
      sleep: 'Sleep Disorders'
    },
    priceRanges: {
      '0-50': '$0 - $50',
      '50-100': '$50 - $100',
      '100-150': '$100 - $150',
      '150+': '$150+'
    },
    availability: {
      morning: 'Morning',
      afternoon: 'Afternoon',
      evening: 'Evening',
      weekend: 'Weekend'
    },
    loading: 'Loading therapists...',
    noResults: 'No therapists found',
    noResultsMessage: 'Try adjusting your filters or check back later for new therapists',
    rating: '{rating} ({count} reviews)',
    specializations: 'Specializations',
    more: '+{count} more',
    nextAvailable: 'Next available: {time}',
    contactForAvailability: 'Contact for availability',
    bookSession: 'Book Session',
    viewProfile: 'View Profile'
  },

  // Therapist Detail
  therapistDetail: {
    backToTherapists: 'Back to Therapists',
    perSession: 'per session',
    about: 'About',
    experience: 'Experience',
    experienceText: 'Licensed therapist with extensive experience in mental health counseling and therapy',
    approach: 'Approach',
    approachText: 'Evidence-based therapeutic approaches tailored to individual client needs and goals',
    languages: 'Languages',
    languagesText: 'English, Arabic',
    availability: 'Availability',
    nextAvailable: 'Next Available',
    sessionDuration: 'Session Duration',
    sessionDurationText: '45 minutes (online)',
    sessionType: 'Session Type',
    sessionTypeText: 'Video call via secure platform',
    reviews: 'Reviews & Ratings',
    noReviews: 'No reviews available yet',
    bookSession: 'Book 45-Minute Session',
    addToCart: 'Add to Cart',
    therapistNotFound: 'Therapist not found',
    therapistNotFoundMessage: 'The therapist you\'re looking for doesn\'t exist or has been removed',
    browseTherapists: 'Browse Therapists'
  },

  // Booking
  booking: {
    title: 'Book Your Session',
    sessionType: 'Session Type',
    selectSessionType: 'Select session type',
    date: 'Preferred Date',
    time: 'Preferred Time',
    notes: 'Session Notes (Optional)',
    notesPlaceholder: 'Any specific topics or concerns you\'d like to discuss...',
    emergencyContact: 'Emergency Contact (Optional)',
    contactName: 'Contact name',
    contactPhone: 'Contact phone',
    terms: 'I agree to the terms and conditions and understand that this is a professional therapy session',
    bookSession: 'Book Session',
    processing: 'Processing...',
    bookingSummary: 'Booking Summary',
    licensedTherapist: 'Licensed Therapist',
    sessionTypeLabel: 'Session Type:',
    dateLabel: 'Date:',
    timeLabel: 'Time:',
    notSelected: 'Not selected',
    sessionFee: 'Session Fee:',
    platformFee: 'Platform Fee:',
    total: 'Total:',
    importantInfo: 'Important Information',
    importantInfoItems: [
      '• Sessions are conducted via secure video call',
      '• Please join 5 minutes before your scheduled time',
      '• Cancellation policy: 24 hours notice required',
      '• Payment is processed securely'
    ],
    therapistNotFound: 'Therapist not found',
    therapistNotFoundMessage: 'The therapist you\'re trying to book with doesn\'t exist or has been removed',
    browseTherapists: 'Browse Therapists'
  },

  // Cart
  cart: {
    title: 'Your Cart',
    empty: {
      title: 'Your cart is empty',
      message: 'Start by browsing our therapists and adding sessions to your cart',
      browseTherapists: 'Browse Therapists'
    },
    session: '{duration}-minute session',
    date: 'Date: {date} at {time}',
    notes: 'Notes:',
    remove: 'Remove',
    orderSummary: 'Order Summary',
    sessions: 'Sessions ({count})',
    platformFee: 'Platform Fee',
    total: 'Total',
    promoCode: 'Promo Code (Optional)',
    enterCode: 'Enter code',
    apply: 'Apply',
    promoApplied: 'Promo code applied: {code} (-${discount})',
    proceedToCheckout: 'Proceed to Checkout',
    processing: 'Processing...',
    continueShopping: 'Continue Shopping',
    importantInfo: 'Important Information',
    importantInfoItems: [
      '• All sessions are conducted online',
      '• 24-hour cancellation policy applies',
      '• Secure payment processing',
      '• Sessions are non-refundable'
    ]
  },

  // Appointments
  appointments: {
    title: 'My Appointments',
    tabs: {
      upcoming: 'Upcoming',
      past: 'Past',
      cancelled: 'Cancelled'
    },
    loading: 'Loading appointments...',
    noAppointments: 'No appointments found',
    noUpcoming: 'You don\'t have any upcoming appointments',
    noPast: 'You don\'t have any past appointments',
    noCancelled: 'You don\'t have any cancelled appointments',
    bookSession: 'Book a Session',
    date: 'Date:',
    time: 'Time:',
    duration: 'Duration:',
    status: 'Status:',
    notes: 'Notes:',
    joinSession: 'Join Session',
    reschedule: 'Reschedule',
    cancel: 'Cancel',
    viewDetails: 'View Details',
    sessionLinkAvailable: 'Session Link Available',
    sessionLinkMessage: 'Click the link below to join your session',
    joinNow: 'Join Now',
    cancelModal: {
      title: 'Cancel Appointment',
      message: 'Are you sure you want to cancel this appointment? This action cannot be undone',
      confirm: 'Yes, Cancel',
      keep: 'No, Keep',
      cancelling: 'Cancelling...'
    },
    statuses: {
      pending: 'Pending',
      confirmed: 'Confirmed',
      completed: 'Completed',
      cancelled: 'Cancelled',
      no_show: 'No Show'
    }
  },

  // Profile
  profile: {
    title: 'My Profile',
    loading: 'Loading profile...',
    personalInfo: 'Personal Information',
    firstName: 'First Name',
    lastName: 'Last Name',
    email: 'Email',
    phone: 'Phone Number',
    dateOfBirth: 'Date of Birth',
    emergencyContact: 'Emergency Contact',
    address: 'Address',
    addressPlaceholder: 'Your address',
    updateProfile: 'Update Profile',
    updating: 'Updating...',
    changePassword: 'Change Password',
    currentPassword: 'Current Password',
    newPassword: 'New Password',
    confirmNewPassword: 'Confirm New Password',
    changePasswordButton: 'Change Password',
    changing: 'Changing...',
    accountSummary: 'Account Summary',
    memberSince: 'Member since:',
    totalSessions: 'Total sessions:',
    accountStatus: 'Account status:',
    active: 'Active',
    quickActions: 'Quick Actions',
    viewAppointments: 'View Appointments',
    takeDiagnosis: 'Take AI Diagnosis',
    logout: 'Logout',
    logoutSuccess: 'Logged out successfully'
  },

  // Language
  language: {
    ar: 'العربية',
    en: 'English',
    switchLanguage: 'Switch Language'
  }
} 