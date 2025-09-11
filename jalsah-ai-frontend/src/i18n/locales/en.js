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
    hide: 'Hide',
    viewDetails: 'Browse Other Appointments',
    added: 'Added',
    download: 'Download',
    upload: 'Upload',
    select: 'Select',
    all: 'All',
    none: 'None',
    required: 'Required',
    optional: 'Optional',
    unknown: 'Unknown',
    na: 'N/A',
    contact: 'Contact',
    pleaseLogin: 'Please login first',
    sessionExpired: 'Session expired. Please login again',
    minutes: 'minutes'
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
    language: 'Language',
    therapistRegister: 'Therapist Registration'
  },

  // Logo
  logo: {
    text: 'Jalsah'
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
      title: 'Sign in to your account',
      or: 'Or',
      createAccount: 'Create a new account',
      rememberMe: 'Remember me',
      forgotPassword: 'Forgot your password?',
      signingIn: 'Signing in...',
      signIn: 'Sign in',
      orContinueWith: 'Or continue with',
      google: 'Google',
      facebook: 'Facebook',
      noAccount: "Don't have an account?",
      signUp: 'Sign up',
      submit: 'Login',
      email: 'Email',
      emailPlaceholder: 'Enter your email',
      whatsapp: 'WhatsApp Number',
      whatsappPlaceholder: 'Enter your WhatsApp number',
      password: 'Password',
      passwordPlaceholder: 'Enter your password',
      errors: {
        invalidCredentials: 'Invalid credentials',
        emailRequired: 'Email is required',
        whatsappRequired: 'WhatsApp number is required',
        passwordRequired: 'Password is required'
      }
    },
    register: {
      title: 'Create your account',
      or: 'Or',
      signInToExisting: 'Sign in to your existing account',
      country: 'Country',
      selectCountry: 'Select your country',
      countries: {
        saudiArabia: 'Saudi Arabia',
        uae: 'United Arab Emirates',
        kuwait: 'Kuwait',
        qatar: 'Qatar',
        bahrain: 'Bahrain',
        oman: 'Oman',
        jordan: 'Jordan',
        lebanon: 'Lebanon',
        egypt: 'Egypt',
        morocco: 'Morocco',
        tunisia: 'Tunisia',
        algeria: 'Algeria',
        libya: 'Libya',
        sudan: 'Sudan',
        iraq: 'Iraq',
        syria: 'Syria',
        palestine: 'Palestine',
        yemen: 'Yemen',
        other: 'Other'
      },
      password: 'Password',
      createPassword: 'Create a password',
      passwordHint: 'Must be at least 8 characters long',
      confirmPassword: 'Confirm password',
      confirmPasswordPlaceholder: 'Confirm your password',
      agreeTo: 'I agree to the',
      termsOfService: 'Terms of Service',
      and: 'and',
      privacyPolicy: 'Privacy Policy',
      creatingAccount: 'Creating account...',
      createAccount: 'Create account',
      orContinueWith: 'Or continue with',
      google: 'Google',
      facebook: 'Facebook',
      haveAccount: 'Already have an account?',
      signIn: 'Sign in',
      passwordMismatch: 'Passwords do not match',
      whatsappPlaceholder: '1234567890',
      errors: {
        passwordMismatch: 'Passwords do not match',
        emailExists: 'Email already exists',
        weakPassword: 'Password is too weak'
      }
    }
  },

  // Verification
  verification: {
    title: 'Verify Your Code',
    subtitle: 'Enter the verification code sent to you',
    emailSentTo: 'Verification code sent to:',
    whatsappSentTo: 'Verification code sent to WhatsApp:',
    code: 'Verification Code',
    codePlaceholder: 'Enter 6-digit code',
    codeHint: 'Enter the 6-digit code sent to you',
    verify: 'Verify Code',
    verifying: 'Verifying...',
    didntReceive: "Didn't receive the code?",
    resendCode: 'Resend Code',
    resendIn: 'Resend in {seconds} seconds',
    sending: 'Sending...',
    backToLogin: 'Back to Login'
  },

  // Toast Messages
  toast: {
    auth: {
      loginSuccess: 'Login successful!',
      loginFailed: 'Login failed',
      registerSuccess: 'Registration successful! Please check your email for verification code.',
      whatsappSentTo: 'Verification code sent to WhatsApp: {contact}',
      registerFailed: 'Registration failed',
      userExistsVerified: 'User already exists and is verified. Please login instead.',
      emailVerified: 'Email verified successfully!',
      whatsappVerified: 'WhatsApp verified successfully!',
      verificationFailed: 'Email verification failed',
      verificationSent: 'Verification code sent successfully!',
      logoutSuccess: 'Logged out successfully',
      sessionExpired: 'Session expired. Please login again.',
      invalidCredentials: 'Invalid credentials',
      emailRequired: 'Email is required',
      passwordRequired: 'Password is required',
      verificationRequired: 'Please verify your email address before logging in. Check your email for verification code.'
    },
    general: {
      error: 'An error occurred',
      success: 'Success',
      loading: 'Loading...',
      networkError: 'Network error. Please try again.',
      serverError: 'Server error. Please try again.'
    }
  },

  // Diagnosis
  diagnosis: {
    title: 'AI-Powered Diagnosis',
    subtitle: 'Answer a few questions to get a personalized diagnosis and find the right therapist for you',
    step1: {
      title: 'How are you feeling?',
      mood: 'Current Mood',
      moodOptions: {
        happy: 'Happy',
        neutral: 'Neutral',
        sad: 'Sad',
        anxious: 'Anxious',
        angry: 'Angry',
        stressed: 'Stressed'
      },
      duration: 'How long have you been feeling this way?',
      durationOptions: {
        less_than_week: 'Less than a week',
        few_weeks: 'A few weeks',
        few_months: 'A few months',
        six_months: 'Six months',
        year_plus: 'A year or more'
      }
    },
    step2: {
      title: 'What symptoms are you experiencing?',
      symptoms: {
        anxiety: 'Anxiety or worry',
        depression: 'Depression or sadness',
        stress: 'Stress or overwhelm',
        sleep: 'Sleep problems',
        appetite: 'Appetite changes',
        energy: 'Low energy or fatigue',
        concentration: 'Difficulty concentrating',
        irritability: 'Irritability or anger',
        isolation: 'Social isolation',
        hopelessness: 'Feelings of hopelessness',
        panic: 'Panic attacks',
        trauma: 'Trauma or PTSD symptoms'
      }
    },
    step3: {
      title: 'How is this affecting your life?',
      impact: 'Impact Level',
      impactOptions: {
        minimal: 'Minimal impact',
        mild: 'Mild impact',
        moderate: 'Moderate impact',
        severe: 'Severe impact',
        extreme: 'Extreme impact'
      },
      affectedAreas: 'Which areas of your life are affected?',
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
      title: 'What are your goals?',
      goals: 'What would you like to achieve through therapy?',
      goalsPlaceholder: 'Describe your goals and what you hope to gain from therapy...',
      preferredApproach: 'Do you have a preferred therapy approach?',
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
    analyzing: 'Analyzing...',
    submit: 'Get Diagnosis'
  },

  // Chat Diagnosis
  chatDiagnosis: {
    title: 'AI Chat Diagnosis',
    subtitle: 'Chat with our AI to get a personalized diagnosis and find the right therapist for you',
    welcome: {
      title: 'Welcome to AI Chat Diagnosis',
      description: 'I\'m here to help you understand your mental health and find the right support.',
      message: 'Hello! I\'m here to help you with a preliminary psychological assessment. Could you tell me which country you\'re from? This will help me understand your cultural context and speak with you in the appropriate language.'
    },
    input: {
      placeholder: 'Type your message here...'
    },
    results: {
      title: 'Diagnosis Complete',
      findTherapists: 'Find Therapists',
      newDiagnosis: 'Start New Diagnosis'
    },
    error: {
      message: 'Failed to get AI response',
      response: 'I\'m sorry, I\'m having trouble processing your message right now. Please try again in a moment.'
    },
    disclaimer: {
      title: 'Medical Disclaimer',
      text: 'This is not medical advice. Please consult a qualified healthcare provider for proper diagnosis and treatment.'
    },
    questionCounter: 'Questions asked: {count}',
    completion: {
      title: 'Diagnosis Complete!',
      message: 'We have completed your diagnosis and are now redirecting you to choose a therapist.',
      redirecting: 'Redirecting to therapist selection...'
    }
  },

  // Diagnosis Results
  diagnosisResults: {
    title: 'Your Diagnosis Results',
    subtitle: 'Based on your responses, here\'s what we found and therapists who can help',
    rediagnose: 'Take Diagnosis Again',
    browseAll: 'Browse All Therapists',
    matchedTherapists: 'Therapists Matched for You',
    loadingTherapists: 'Finding the best therapists for you...',
    noTherapistsFound: 'No Therapists Found',
    noTherapistsDescription: 'We couldn\'t find therapists specifically matched to your diagnosis, but you can browse all available therapists.',
    browseAllTherapists: 'Browse All Therapists',
    errorLoadingTherapists: 'Failed to load matched therapists',
    defaultTitle: 'General Mental Health Support',
    defaultDescription: 'Based on your responses, you may benefit from general mental health support and therapy.',
    showMore: 'Show More Therapists',
    moreTherapists: 'more',
    sortBy: 'Sort by',
    sortByOrder: 'Order',
    sortByPrice: 'Price',
    sortByAppointment: 'Appointment',
    any: 'Any',
    orderAsc: 'Ascending',
    orderDesc: 'Descending',
    priceLowest: 'Lowest',
    priceHighest: 'Highest',
    appointmentNearest: 'Nearest',
    appointmentFarthest: 'Farthest',
    simulatedResults: {
      anxiety: {
        title: 'Anxiety Disorder',
        description: 'You may be experiencing symptoms of anxiety disorder. Professional therapy can help you develop coping strategies and reduce anxiety symptoms.'
      },
      depression: {
        title: 'Depression',
        description: 'Your responses suggest symptoms of depression. Therapy can help you understand and manage these feelings effectively.'
      },
      stress: {
        title: 'Stress Management',
        description: 'You\'re experiencing significant stress that\'s impacting your daily life. Stress management therapy can help you develop healthy coping mechanisms.'
      },
      general: {
        title: 'Mental Health Support',
        description: 'You may benefit from general mental health support. A qualified therapist can help you work through your concerns and improve your well-being.'
      }
    }
  },

  // Therapists
  therapists: {
    title: 'Find Your Therapist',
    subtitle: 'Browse our qualified therapists and find the perfect match for your needs',
    loading: 'Loading therapists...',
    bioDefault: 'Professional therapist with expertise in mental health and well-being.',
    whyBestForDiagnosis: 'Why is this therapist the best for your diagnosis?',
    bookSession: 'Book Session',
    viewProfile: 'View Profile',
    viewDetails: 'View Details',
    more: '+{count} more',
    noSlotsAvailable: 'Contact for availability',
    availableToday: 'Available today at {time}',
    availableTomorrow: 'Available tomorrow at {time}',
    availableOn: 'Available {date} at {time}',
    contactForAvailability: 'Contact for availability',
    specializations: 'Specializations',
    sortBy: 'Sort by',
    sorting: {
      best: 'The Best',
      priceLow: 'Lowest Price',
      nearest: 'Nearest'
    },
    loadingMore: 'Loading more...',
    allLoaded: 'All therapists loaded',
    filters: {
      specialization: 'Specialization',
      allSpecializations: 'All Specializations',
      search: 'Search',
      searchPlaceholder: 'Search by therapist name...',
      priceRange: 'Price Range',
      anyPrice: 'Any Price',
      lowestPrice: 'Lowest Price',
      highestPrice: 'Highest Price',
      nearestAppointment: 'Nearest Appointment',
      anyTime: 'Any Time',
      closest: 'Closest',
      farthest: 'Farthest',
      sortBy: 'Sort By',
      random: 'Random',
      highestRated: 'Highest Rated'
    },
    showMore: 'Show More',
    moreTherapists: 'more therapists',
    noResults: 'No therapists found',
    noResultsMessage: 'Try adjusting your search criteria or browse all therapists'
  },

  // Date and Time
  dateTime: {
    am: 'AM',
    pm: 'PM',
    at: 'at',
    months: {
      january: 'January',
      february: 'February',
      march: 'March',
      april: 'April',
      may: 'May',
      june: 'June',
      july: 'July',
      august: 'August',
      september: 'September',
      october: 'October',
      november: 'November',
      december: 'December'
    },
    monthsShort: {
      jan: 'Jan',
      feb: 'Feb',
      mar: 'Mar',
      apr: 'Apr',
      may: 'May',
      jun: 'Jun',
      jul: 'Jul',
      aug: 'Aug',
      sep: 'Sep',
      oct: 'Oct',
      nov: 'Nov',
      dec: 'Dec'
    },
    days: {
      sunday: 'Sunday',
      monday: 'Monday',
      tuesday: 'Tuesday',
      wednesday: 'Wednesday',
      thursday: 'Thursday',
      friday: 'Friday',
      saturday: 'Saturday'
    },
    daysShort: {
      sun: 'Sun',
      mon: 'Mon',
      tue: 'Tue',
      wed: 'Wed',
      thu: 'Thu',
      fri: 'Fri',
      sat: 'Sat'
    }
  },

  // Therapist Detail
  therapistDetail: {
    backToTherapists: 'Back to Therapists',
    reviews: 'reviews',
    perSession: 'for a 45-minute session',
    bioDefault: 'Experienced therapist specializing in mental health and well-being. Committed to providing compassionate, evidence-based care to help clients achieve their mental health goals.',
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
    viewDetails: 'View Details',
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
    minutes: 'minutes',
    loadError: 'Failed to load therapist information',
    submitSuccess: 'Booking submitted successfully!',
    submitError: 'Failed to submit booking. Please try again.',
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
    proceedToPayment: 'Proceed to Payment',
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

  // Cart page specific translations
  shoppingCart: 'Shopping Cart',
  cartDescription: 'Review your selected appointments and complete your booking',
  loadingCart: 'Loading cart...',
  errorLoadingCart: 'Error loading cart',
  emptyCart: 'Cart is empty',
  emptyCartDescription: 'You haven\'t added any appointments to your cart yet',
  subtotal: 'Subtotal',
  minutes: 'minutes',
  appointments: 'Appointments',
  orderSummary: 'Order Summary',
  total: 'Total',
  proceedToCheckout: 'Proceed to Checkout',
  proceedToPayment: 'Proceed to Payment',
  remove: 'Remove',
  duration: 'Duration',

  // Checkout page translations
  checkout: 'Checkout',
  completeYourBooking: 'Complete your booking',
  loadingCheckout: 'Loading checkout...',
  errorLoadingCheckout: 'Error loading checkout',
  orderCreated: 'Order Created Successfully!',
  redirectingToPayment: 'Redirecting you to the payment page...',
  paymentMethod: 'Payment Method',
  cashPayment: 'Cash Payment',
  cardPayment: 'Card Payment',
  paymentSummary: 'Payment Summary',
  completePayment: 'Complete Payment',
  backToCart: 'Back to Cart',

  // Appointments Page
  appointmentsPage: {
    title: 'My Appointments',
    loading: 'Loading appointments...',
    booking: 'Booking...',
    tabs: {
      upcoming: 'Upcoming',
      past: 'Past',
      cancelled: 'Cancelled'
    },
    date: 'Date',
    time: 'Time',
    duration: 'Duration',
    status: 'Status',
    notes: 'Notes',
    joinSession: 'Join Session',
    reschedule: 'Reschedule',
    cancel: 'Cancel',
    viewDetails: 'View Details',
    bookWithSameTherapist: 'Book a new appointment with the same therapist',
    sessionLinkAvailable: 'Session Link Available',
    sessionLinkMessage: 'Click the link below to join your session',
    joinNow: 'Join Now',
    noAppointments: 'No Appointments',
    noUpcoming: 'You have no upcoming appointments.',
    noPast: 'You have no past appointments.',
    noCancelled: 'You have no cancelled appointments.',
    bookSession: 'Book Session',
    cancelTitle: 'Cancel Appointment',
    cancelMessage: 'Are you sure you want to cancel this appointment? This action cannot be undone.',
    cancelling: 'Cancelling...',
    yesCancel: 'Yes, Cancel',
    noKeep: 'No, Keep',
    // Status values
    statusConfirmed: 'Confirmed',
    statusPending: 'Pending',
    statusCompleted: 'Completed',
    statusCancelled: 'Cancelled',
    statusNoShow: 'No Show',
    // Time units
    minutes: 'minutes',
    // Additional fields
    therapist: 'Therapist',
    session: 'Session'
  },

  // Therapist Appointment Page
  therapistAppointment: {
    title: 'Book Appointment',
    subtitle: 'Select a time slot to book your session',
    bookSession: 'Book Session',
    bookNow: 'Book Now',
    booking: 'Booking...',
    addedToCart: 'Added to cart successfully',
    bookingError: 'Error booking appointment',
    loadError: 'Error loading therapist information',
    noSlotsAvailable: 'No available time slots at the moment'
  },

  // Reschedule Page
  reschedule: {
    title: 'Reschedule Appointment',
    subtitle: 'Select a new date and time for your appointment',
    currentAppointment: 'Current Appointment',
    therapist: 'Therapist',
    currentDate: 'Current Date',
    currentTime: 'Current Time',
    duration: 'Duration',
    selectNewTime: 'Select New Time',
    selectDate: 'Select Date',
    selectTime: 'Select Time',
    noSlotsAvailable: 'No available time slots for this date',
    confirmReschedule: 'Confirm Reschedule',
    rescheduling: 'Rescheduling...',
    success: 'Appointment rescheduled successfully',
    error: 'Failed to reschedule appointment',
    alreadyRescheduled: 'This appointment has already been rescheduled once',
    alreadyRescheduledTitle: 'Already Rescheduled',
    alreadyRescheduledMessage: 'This appointment has already been rescheduled once. Multiple reschedules are not allowed.',
    cannotReschedule: 'Cannot Reschedule',
    leaveMeetingTitle: 'Leave Meeting?',
    leaveMeetingMessage: 'Are you sure you want to leave the session? You will be redirected to your appointments.',
    leaveMeeting: 'Leave Meeting',
    stayInMeeting: 'Stay in Meeting',
    prescription: 'Prescription',
    prescriptionRequested: 'Prescription Requested',
    prescriptionServices: 'Prescription Services',
    bookFreeAppointment: 'Book Free Appointment',
    bookAppointment: 'Book Appointment',
    confirmBooking: 'Confirm Booking',
    confirmBookingMessage: 'Are you sure you want to book this appointment?',
    noAvailableSlots: 'No available slots at the moment',
    confirmMedication: 'Do you think the client needs medication and would you like to refer them to a psychiatrist (free of charge) to prescribe medication alongside your sessions?',
    preliminaryDiagnosis: 'Preliminary diagnosis of the client according to your observation',
    symptoms: 'Symptoms that you believe require medication',
    request: 'Request',
    cancel: 'Cancel',
    yes: 'Yes',
    no: 'No',
    close: 'Close',
    successMessage: 'Prescription service request submitted successfully.',
    errorMessage: 'An error occurred. Please try again.',
    reasonForReferral: 'Reason for Referral',
    pending: 'Pending',
    confirmed: 'Confirmed',
    viewAppointment: 'View Appointment'
  },

  // Prescription
  prescription: {
    prescriptionServices: 'Prescription Services',
    prescriptionRequested: 'Prescription Requested',
    completedPrescriptions: 'My Prescriptions',
    bookFreeAppointment: 'Book Free Appointment',
    bookAppointment: 'Book Appointment',
    confirmBooking: 'Confirm Booking',
    confirmBookingMessage: 'Are you sure you want to book this appointment?',
    joinMeeting: 'Join Meeting',
    rochtahSession: 'Rochtah Session',
    roomName: 'Room Name',
    startMeeting: 'Start Meeting',
    pending: 'Pending',
    confirmed: 'Confirmed',
    viewAppointment: 'View Appointment',
    viewPrescription: 'View Prescription',
    prescriptionDetails: 'Prescription Details',
    prescribedBy: 'Prescribed by',
    prescribedAt: 'Prescribed on',
    medications: 'Medications',
    dosageInstructions: 'Dosage Instructions',
    doctorNotes: 'Doctor Notes',
    initialDiagnosis: 'Initial Diagnosis',
    symptoms: 'Symptoms',
    reasonForReferral: 'Reason for Referral',
    prescriptionText: 'Prescription',
    noPrescriptions: 'No prescriptions available',
    loadingPrescriptions: 'Loading prescriptions...'
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
    contactName: 'Contact name',
    contactPhone: 'Contact phone',
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
  },

  // Therapist Register
  therapistRegister: {
    title: 'Therapist Registration',
    subtitle: 'Register as a therapist to join the platform.',
    name: 'Name (Arabic)',
    nameEn: 'Name (English)',
    email: 'Email',
    phone: 'Phone',
    whatsapp: 'WhatsApp',
    specialty: 'Specialty / Job Title',
    profileImage: 'Profile Image',
    identityFront: 'Identity Front',
    identityBack: 'Identity Back',
    certificates: 'Certificates',
    password: 'Password',
    passwordConfirm: 'Confirm Password',
    passwordAuto: 'A password will be generated and sent to your email.',
    acceptTerms: 'I accept the terms and conditions',
    submit: 'Register',
    submitting: 'Registering...',
    success: 'Registration successful! Please check your email.',
    error: 'Registration failed. Please try again.',
    applicationSubmitted: 'Your application has been submitted and is pending admin approval.'
  },

  // Therapist Details
  therapistDetails: {
    title: 'Therapist Details',
    loading: 'Loading details...',
    error: 'Error loading details',
    loadError: 'Failed to load details. Please try again.',
    bio: 'Biography',
    noCertificates: 'No certificates available',
    noCertificatesMessage: 'This therapist has not uploaded any certificates yet.',
    noDescription: 'No description available',
    personalInfo: 'Personal Information',
    name: 'Name',
    nameEn: 'Name (English)',
    specialty: 'Specialty',
    jalsahAiName: 'Jalsah AI Name',
    email: 'Email',
    phone: 'Phone',
    whatsapp: 'WhatsApp',
    applicationInfo: 'Application Information',
    applicationDate: 'Application Date',
    approvalDate: 'Approval Date',
    certificatesCount: 'Certificates',
    certificates: 'Certificates',
    downloadFile: 'Download File',
    bookAppointment: 'Book Appointment',
    earliestAvailable: 'Earliest Available',
    bookThis: 'Book This',
    bookAnother: 'Book Another Appointment',
    loadingDates: 'Loading available dates...',
    availableTimes: 'Available Times',
    noTimeSlots: 'No time slots available for this date',
    noAvailableDates: 'No available dates found',
    appointmentAdded: 'Appointment added to cart successfully. The appointment will be automatically removed after half an hour if payment not completed.',
    appointmentRemoved: 'Appointment removed from cart successfully',
    differentTherapistTitle: 'Different Therapist',
    differentTherapistMessage: 'You have appointments from another therapist in your cart. Adding this appointment will clear your cart to book with a different therapist.',
    inCart: 'In Cart',
    viewAllCertificates: 'View all certificates'
  },

  // Certificates
  certificates: {
    title: 'Certificates & Credentials',
    loading: 'Loading certificates...',
    error: 'Error loading certificates',
    loadError: 'Failed to load certificates. Please try again.',
    noCertificates: 'No certificates available',
    noCertificatesMessage: 'This therapist has not uploaded any certificates yet.',
    noDescription: 'No description available'
  },

  // Session Management
  session: {
    title: 'Session',
    with: 'with',
    loading: 'Loading session...',
    error: 'Error loading session',
    notFound: 'Session not found',
    notFoundDescription: 'The session you are looking for does not exist or you do not have permission to access it.',
    backToAppointments: 'Back to Appointments',
    therapist: 'Therapist',
    time: 'Session Time',
    duration: 'Duration',
    minutes: 'minutes',
    actions: 'Session Actions',
    join: 'Join Session',
    joining: 'Joining...',
    joinError: 'Failed to join session',
    joined: 'Successfully joined session',
    startMeeting: 'Start Meeting',
    starting: 'Starting...',
    meetingStarted: 'Meeting started successfully',
    startError: 'Failed to start meeting',
    end: 'End Session',
    ending: 'Ending...',
    endError: 'Failed to end session',
    ended: 'Session ended successfully',
    confirmEnd: 'Are you sure you want to end this session?',
    confirmEndAbsent: 'Are you sure you want to end this session and mark the patient as absent?',
    confirmEndTitle: 'End Session',
    confirmEndYes: 'Yes, End Session',
    confirmEndNo: 'Cancel',
    endSessionTitle: 'End Session',
    markCompletedTitle: 'Mark as Completed',
    markCompleted: 'Mark Session as Completed',
    marking: 'Marking as completed...',
    markedCompleted: 'Session marked as completed successfully',
    markCompletedError: 'Failed to mark session as completed',
    confirmMarkCompletedTitle: 'Mark Session as Completed',
    confirmMarkCompleted: 'Are you sure you want to mark this session as completed?',
    confirmMarkCompletedYes: 'Yes, Mark as Completed',
    confirmMarkCompletedNo: 'Cancel',
    patientAttended: 'Patient attended the session',
    patientAbsent: 'Patient was absent',
    absenceWarning: '15-Minute Rule',
    wait15Minutes: 'You must wait at least 15 minutes after the session start time before marking a patient as absent.',
    waitingForTherapist: 'Waiting for therapist...',
    waitingDescription: 'Please wait for your therapist to join the session.',
    notAvailable: 'Session not available',
    meetingRoom: 'Meeting Room',
    timeExpired: 'Session time has expired',
    loadingMeeting: 'Loading meeting room...',
    meetingError: 'Error loading meeting room',
    connecting: 'Connecting...',
    showMeeting: 'Show Meeting',
    instructions: 'Session Instructions',
    instruction1: 'Ensure you have a stable internet connection',
    instruction2: 'Find a quiet, private space for your session',
    instruction3: 'Have your camera and microphone ready',
    loadError: 'Failed to load session details',
    status: {
      pending: 'Pending',
      confirmed: 'Confirmed',
      completed: 'Completed',
      cancelled: 'Cancelled',
      noShow: 'No Show'
    },
    reason: {
      notConfirmed: 'Session is not confirmed yet',
      tooEarly: 'Session has not started yet',
      tooLate: 'Session time has passed',
      notAuthorized: 'You are not authorized to join this session',
      completed: 'Session has ended',
      cancelled: 'Session has been cancelled',
      unknown: 'Session is not available at this time'
    }
  },

  // Not Found
  notFound: {
    title: 'Page Not Found',
    message: 'The page you are looking for doesn\'t exist or has been moved.',
    goHome: 'Go to Home',
    goBack: 'Go Back'
  },

  // Appointment Change Terms
  appointmentChangeTerms: 'Appointment Change Terms'
} 