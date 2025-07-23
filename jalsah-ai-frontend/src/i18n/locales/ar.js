export default {
  // Common
  common: {
    loading: 'جاري التحميل...',
    error: 'حدث خطأ',
    success: 'تم بنجاح',
    cancel: 'إلغاء',
    save: 'حفظ',
    edit: 'تعديل',
    delete: 'حذف',
    confirm: 'تأكيد',
    back: 'رجوع',
    next: 'التالي',
    previous: 'السابق',
    submit: 'إرسال',
    search: 'بحث',
    filter: 'تصفية',
    sort: 'ترتيب',
    view: 'عرض',
    add: 'إضافة',
    remove: 'إزالة',
    close: 'إغلاق',
    yes: 'نعم',
    no: 'لا',
    ok: 'موافق',
    retry: 'إعادة المحاولة',
    refresh: 'تحديث',
    download: 'تحميل',
    upload: 'رفع',
    select: 'اختيار',
    all: 'الكل',
    none: 'لا شيء',
    required: 'مطلوب',
    optional: 'اختياري',
    unknown: 'غير معروف',
    na: 'غير متوفر',
    contact: 'تواصل'
  },

  // Navigation
  nav: {
    home: 'الرئيسية',
    therapists: 'المعالجون',
    diagnosis: 'التشخيص',
    appointments: 'المواعيد',
    profile: 'الملف الشخصي',
    cart: 'السلة',
    login: 'تسجيل الدخول',
    register: 'إنشاء حساب',
    logout: 'تسجيل الخروج',
    language: 'اللغة',
    therapistRegister: 'تسجيل المعالج'
  },

  // Home Page
  home: {
    hero: {
      title: 'رعاية نفسية ذكية ومتطورة',
      subtitle: 'احصل على تشخيص ذكي وابحث عن المعالج المناسب لك',
      cta: 'ابدأ التشخيص الذكي',
      secondaryCta: 'تصفح المعالجين'
    },
    features: {
      title: 'لماذا تختار جلسة الذكية؟',
      aiDiagnosis: {
        title: 'تشخيص ذكي',
        description: 'احصل على تشخيص دقيق باستخدام الذكاء الاصطناعي'
      },
      expertTherapists: {
        title: 'معالجون خبراء',
        description: 'معالجون مرخصون ومؤهلون في مجال الصحة النفسية'
      },
      onlineSessions: {
        title: 'جلسات أونلاين',
        description: 'جلسات آمنة ومريحة من منزلك'
      },
      flexibleScheduling: {
        title: 'مواعيد مرنة',
        description: 'احجز المواعيد التي تناسب جدولك'
      }
    },
    howItWorks: {
      title: 'كيف يعمل النظام؟',
      step1: {
        title: 'التشخيص الذكي',
        description: 'أكمل استبيان قصير للحصول على تشخيص مخصص'
      },
      step2: {
        title: 'اختيار المعالج',
        description: 'اختر من قائمة المعالجين المطابقة لاحتياجاتك'
      },
      step3: {
        title: 'حجز الجلسة',
        description: 'احجز جلستك وادفع بسهولة وأمان'
      },
      step4: {
        title: 'ابدأ العلاج',
        description: 'انضم لجلستك عبر الإنترنت وابدأ رحلة الشفاء'
      }
    }
  },

  // Authentication
  auth: {
    login: {
      title: 'تسجيل الدخول إلى حسابك',
      or: 'أو',
      createAccount: 'إنشاء حساب جديد',
      rememberMe: 'تذكرني',
      forgotPassword: 'نسيت كلمة المرور؟',
      signingIn: 'جاري تسجيل الدخول...',
      signIn: 'تسجيل الدخول',
      orContinueWith: 'أو تابع باستخدام',
      google: 'جوجل',
      facebook: 'فيسبوك',
      email: 'البريد الإلكتروني',
      emailPlaceholder: 'أدخل بريدك الإلكتروني',
      password: 'كلمة المرور',
      passwordPlaceholder: 'أدخل كلمة المرور',
      errors: {
        invalidCredentials: 'بيانات الدخول غير صحيحة',
        emailRequired: 'البريد الإلكتروني مطلوب',
        passwordRequired: 'كلمة المرور مطلوبة'
      }
    },
    register: {
      title: 'إنشاء حسابك',
      or: 'أو',
      signInToExisting: 'تسجيل الدخول إلى حسابك الحالي',
      country: 'الدولة',
      selectCountry: 'اختر دولتك',
      countries: {
        saudiArabia: 'المملكة العربية السعودية',
        uae: 'الإمارات العربية المتحدة',
        kuwait: 'الكويت',
        qatar: 'قطر',
        bahrain: 'البحرين',
        oman: 'عمان',
        jordan: 'الأردن',
        lebanon: 'لبنان',
        egypt: 'مصر',
        morocco: 'المغرب',
        tunisia: 'تونس',
        algeria: 'الجزائر',
        libya: 'ليبيا',
        sudan: 'السودان',
        iraq: 'العراق',
        syria: 'سوريا',
        palestine: 'فلسطين',
        yemen: 'اليمن',
        other: 'أخرى'
      },
      password: 'كلمة المرور',
      createPassword: 'أنشئ كلمة مرور',
      passwordHint: 'يجب أن تكون 8 أحرف على الأقل',
      confirmPassword: 'تأكيد كلمة المرور',
      confirmPasswordPlaceholder: 'أكد كلمة المرور',
      agreeTo: 'أوافق على',
      termsOfService: 'شروط الخدمة',
      and: 'و',
      privacyPolicy: 'سياسة الخصوصية',
      creatingAccount: 'جاري إنشاء الحساب...',
      createAccount: 'إنشاء حساب',
      orContinueWith: 'أو تابع باستخدام',
      google: 'جوجل',
      facebook: 'فيسبوك',
      firstName: 'الاسم الأول',
      lastName: 'اسم العائلة',
      age: 'العمر',
      agePlaceholder: 'عمرك',
      email: 'البريد الإلكتروني',
      emailPlaceholder: 'أدخل بريدك الإلكتروني',
      phone: 'رقم الهاتف',
      phonePlaceholder: '+1234567890',
      whatsapp: 'رقم الواتساب',
      whatsappPlaceholder: '+1234567890',
      errors: {
        passwordMismatch: 'كلمات المرور غير متطابقة',
        emailExists: 'البريد الإلكتروني مستخدم بالفعل',
        weakPassword: 'كلمة المرور ضعيفة جداً'
      }
    }
  },

  // Diagnosis
  diagnosis: {
    title: 'التشخيص الذكي للصحة النفسية',
    subtitle: 'احصل على تقييم مخصص لمساعدتك في العثور على المعالج المناسب',
    step1: {
      title: 'الخطوة الأولى: أخبرنا عن نفسك',
      mood: 'كيف تصف حالتك المزاجية الحالية؟',
      moodOptions: {
        happy: 'سعيد ومطمئن',
        neutral: 'محايد',
        sad: 'حزين أو مكتئب',
        anxious: 'قلق أو متوتر',
        angry: 'غاضب أو عصبي',
        stressed: 'متوتر أو مرهق'
      },
      duration: 'منذ متى وأنت تشعر بهذه الطريقة؟',
      durationOptions: {
        less_than_week: 'أقل من أسبوع',
        few_weeks: 'بضعة أسابيع',
        few_months: 'بضعة أشهر',
        six_months: '6 أشهر أو أكثر',
        year_plus: 'أكثر من سنة'
      }
    },
    step2: {
      title: 'الخطوة الثانية: ما هي الأعراض التي تعاني منها؟',
      symptoms: {
        anxiety: 'قلق أو قلق مفرط',
        depression: 'اكتئاب أو مزاج منخفض',
        stress: 'مستويات عالية من التوتر',
        sleep: 'مشاكل في النوم',
        appetite: 'تغيرات في الشهية',
        energy: 'طاقة منخفضة أو إرهاق',
        concentration: 'صعوبة في التركيز',
        irritability: 'تهيج أو غضب',
        isolation: 'انسحاب اجتماعي',
        hopelessness: 'مشاعر اليأس',
        panic: 'نوبات ذعر',
        trauma: 'أعراض متعلقة بالصدمة'
      }
    },
    step3: {
      title: 'الخطوة الثالثة: كيف يؤثر هذا على حياتك؟',
      impact: 'إلى أي مدى تؤثر هذه الأعراض على حياتك اليومية؟',
      impactOptions: {
        minimal: 'تأثير ضئيل',
        mild: 'تأثير خفيف',
        moderate: 'تأثير متوسط',
        severe: 'تأثير شديد',
        extreme: 'تأثير شديد جداً'
      },
      affectedAreas: 'ما هي مجالات حياتك الأكثر تأثراً؟',
      areas: {
        work: 'العمل أو المهنة',
        relationships: 'العلاقات',
        family: 'الحياة الأسرية',
        social: 'الحياة الاجتماعية',
        health: 'الصحة البدنية',
        finances: 'الوضع المالي',
        hobbies: 'الهوايات والاهتمامات',
        daily_routine: 'الروتين اليومي'
      }
    },
    step4: {
      title: 'الخطوة الرابعة: ما هي أهدافك من العلاج؟',
      goals: 'ماذا تريد أن تحقق من خلال العلاج؟',
      goalsPlaceholder: 'صف أهدافك وما تأمل في تحقيقه...',
      preferredApproach: 'ما هو نوع العلاج الذي يثير اهتمامك أكثر؟',
      approachOptions: {
        none: 'لا تفضيل',
        cbt: 'العلاج المعرفي السلوكي',
        psychodynamic: 'العلاج النفسي الديناميكي',
        humanistic: 'العلاج الإنساني',
        mindfulness: 'العلاج القائم على اليقظة',
        solution_focused: 'العلاج المتمركز على الحلول'
      }
    },
    progress: 'الخطوة {step} من 4',
    complete: 'اكتمل {percent}%',
    submit: 'احصل على تشخيصي',
    analyzing: 'جاري التحليل...',
    success: 'تم التشخيص! نبحث عن المعالجين المناسبين لك...'
  },

  // Therapists
  therapists: {
    title: 'ابحث عن معالجك المثالي',
    subtitle: 'تصفح معالجينا المؤهلين واعثر على المناسب لك',
    filters: {
      specialization: 'التخصص',
      allSpecializations: 'جميع التخصصات',
      priceRange: 'نطاق السعر',
      anyPrice: 'أي سعر',
      availability: 'التوفر',
      anyTime: 'أي وقت',
      sortBy: 'ترتيب حسب',
      highestRated: 'الأعلى تقييماً',
      lowestPrice: 'أقل سعر',
      highestPrice: 'أعلى سعر',
      nearestAppointment: 'أقرب موعد'
    },
    specializations: {
      anxiety: 'اضطرابات القلق',
      depression: 'الاكتئاب',
      stress: 'إدارة التوتر',
      relationships: 'مشاكل العلاقات',
      trauma: 'الصدمة واضطراب ما بعد الصدمة',
      addiction: 'الإدمان',
      eating: 'اضطرابات الأكل',
      sleep: 'اضطرابات النوم'
    },

    availability: {
      morning: 'صباحاً',
      afternoon: 'ظهراً',
      evening: 'مساءً',
      weekend: 'عطلة نهاية الأسبوع'
    },
    loading: 'جاري تحميل المعالجين...',
    noResults: 'لم يتم العثور على معالجين',
    noResultsMessage: 'جرب تعديل الفلاتر أو تحقق لاحقاً من المعالجين الجدد',
    rating: '{rating} ({count} تقييم)',
    specializations: 'التخصصات',
    more: '+{count} المزيد',
    nextAvailable: 'التوفر التالي: {time}',
    contactForAvailability: 'تواصل للتحقق من التوفر',
    bookSession: 'حجز جلسة',
    viewProfile: 'عرض الملف الشخصي',
    bioDefault: 'معالج ذو خبرة متخصص في الصحة النفسية والعافية. ملتزم بتقديم رعاية شفقة قائمة على الأدلة لمساعدة العملاء على تحقيق أهدافهم في الصحة النفسية.'
  },

  // Therapist Detail
  therapistDetail: {
    backToTherapists: 'العودة للمعالجين',
    reviews: 'تقييمات',
    perSession: 'للساعة الواحدة',
    bioDefault: 'معالج ذو خبرة متخصص في الصحة النفسية والعافية. ملتزم بتقديم رعاية شفقة قائمة على الأدلة لمساعدة العملاء على تحقيق أهدافهم في الصحة النفسية.',
    about: 'حول',
    experience: 'الخبرة',
    experienceText: 'معالج مرخص بخبرة واسعة في الاستشارة النفسية والعلاج',
    approach: 'النهج',
    approachText: 'نهج علاجية قائمة على الأدلة ومخصصة لاحتياجات العميل وأهدافه',
    languages: 'اللغات',
    languagesText: 'العربية، الإنجليزية',
    availability: 'التوفر',
    nextAvailable: 'التوفر التالي',
    sessionDuration: 'مدة الجلسة',
    sessionDurationText: '45 دقيقة (أونلاين)',
    sessionType: 'نوع الجلسة',
    sessionTypeText: 'مكالمة فيديو عبر منصة آمنة',
    reviews: 'التقييمات والمراجعات',
    noReviews: 'لا توجد مراجعات متاحة بعد',
    bookSession: 'حجز جلسة 45 دقيقة',
    addToCart: 'إضافة للسلة',
    therapistNotFound: 'المعالج غير موجود',
    therapistNotFoundMessage: 'المعالج الذي تبحث عنه غير موجود أو تم إزالته',
    browseTherapists: 'تصفح المعالجين'
  },

  // Booking
  booking: {
    title: 'احجز جلستك',
    sessionType: 'نوع الجلسة',
    selectSessionType: 'اختر نوع الجلسة',
    date: 'التاريخ المفضل',
    time: 'الوقت المفضل',
    notes: 'ملاحظات الجلسة (اختياري)',
    notesPlaceholder: 'أي مواضيع أو مخاوف محددة تود مناقشتها...',
    emergencyContact: 'جهة اتصال للطوارئ (اختياري)',
    contactName: 'اسم جهة الاتصال',
    contactPhone: 'هاتف جهة الاتصال',
    terms: 'أوافق على الشروط والأحكام وأفهم أن هذه جلسة علاجية مهنية',
    bookSession: 'حجز الجلسة',
    processing: 'جاري المعالجة...',
    bookingSummary: 'ملخص الحجز',
    licensedTherapist: 'معالج مرخص',
    sessionTypeLabel: 'نوع الجلسة:',
    dateLabel: 'التاريخ:',
    timeLabel: 'الوقت:',
    notSelected: 'غير محدد',
    sessionFee: 'رسوم الجلسة:',
    platformFee: 'رسوم المنصة:',
    total: 'المجموع:',
    importantInfo: 'معلومات مهمة',
    importantInfoItems: [
      '• تُعقد الجلسات عبر مكالمة فيديو آمنة',
      '• يرجى الانضمام قبل 5 دقائق من الموعد المحدد',
      '• سياسة الإلغاء: مطلوب إشعار قبل 24 ساعة',
      '• يتم معالجة الدفع بأمان'
    ],
    therapistNotFound: 'المعالج غير موجود',
    therapistNotFoundMessage: 'المعالج الذي تحاول الحجز معه غير موجود أو تم إزالته',
    browseTherapists: 'تصفح المعالجين'
  },

  // Cart
  cart: {
    title: 'سلتك',
    empty: {
      title: 'سلتك فارغة',
      message: 'ابدأ بتصفح معالجينا وإضافة الجلسات لسلتك',
      browseTherapists: 'تصفح المعالجين'
    },
    session: 'جلسة {duration} دقيقة',
    date: 'التاريخ: {date} في {time}',
    notes: 'ملاحظات:',
    remove: 'إزالة',
    orderSummary: 'ملخص الطلب',
    sessions: 'الجلسات ({count})',
    platformFee: 'رسوم المنصة',
    total: 'المجموع',
    promoCode: 'كود الخصم (اختياري)',
    enterCode: 'أدخل الكود',
    apply: 'تطبيق',
    promoApplied: 'تم تطبيق كود الخصم: {code} (-${discount})',
    proceedToCheckout: 'المتابعة للدفع',
    processing: 'جاري المعالجة...',
    continueShopping: 'مواصلة التسوق',
    importantInfo: 'معلومات مهمة',
    importantInfoItems: [
      '• جميع الجلسات تُعقد أونلاين',
      '• تطبق سياسة الإلغاء قبل 24 ساعة',
      '• معالجة دفع آمنة',
      '• الجلسات غير قابلة للاسترداد'
    ]
  },

  // Appointments
  appointments: {
    title: 'مواعيدي',
    tabs: {
      upcoming: 'القادمة',
      past: 'السابقة',
      cancelled: 'الملغاة'
    },
    loading: 'جاري تحميل المواعيد...',
    noAppointments: 'لم يتم العثور على مواعيد',
    noUpcoming: 'ليس لديك مواعيد قادمة',
    noPast: 'ليس لديك مواعيد سابقة',
    noCancelled: 'ليس لديك مواعيد ملغاة',
    bookSession: 'حجز جلسة',
    date: 'التاريخ:',
    time: 'الوقت:',
    duration: 'المدة:',
    status: 'الحالة:',
    notes: 'ملاحظات:',
    joinSession: 'انضم للجلسة',
    reschedule: 'إعادة جدولة',
    cancel: 'إلغاء',
    viewDetails: 'عرض التفاصيل',
    sessionLinkAvailable: 'رابط الجلسة متوفر',
    sessionLinkMessage: 'انقر على الرابط أدناه للانضمام لجلستك',
    joinNow: 'انضم الآن',
    cancelModal: {
      title: 'إلغاء الموعد',
      message: 'هل أنت متأكد من أنك تريد إلغاء هذا الموعد؟ لا يمكن التراجع عن هذا الإجراء',
      confirm: 'نعم، إلغاء',
      keep: 'لا، احتفظ',
      cancelling: 'جاري الإلغاء...'
    },
    statuses: {
      pending: 'في الانتظار',
      confirmed: 'مؤكد',
      completed: 'مكتمل',
      cancelled: 'ملغي',
      no_show: 'لم يحضر'
    }
  },

  // Profile
  profile: {
    title: 'ملفي الشخصي',
    loading: 'جاري تحميل الملف الشخصي...',
    personalInfo: 'المعلومات الشخصية',
    firstName: 'الاسم الأول',
    lastName: 'اسم العائلة',
    email: 'البريد الإلكتروني',
    phone: 'رقم الهاتف',
    dateOfBirth: 'تاريخ الميلاد',
    emergencyContact: 'جهة اتصال للطوارئ',
    address: 'العنوان',
    addressPlaceholder: 'عنوانك',
    updateProfile: 'تحديث الملف الشخصي',
    updating: 'جاري التحديث...',
    changePassword: 'تغيير كلمة المرور',
    currentPassword: 'كلمة المرور الحالية',
    newPassword: 'كلمة المرور الجديدة',
    confirmNewPassword: 'تأكيد كلمة المرور الجديدة',
    changePasswordButton: 'تغيير كلمة المرور',
    changing: 'جاري التغيير...',
    accountSummary: 'ملخص الحساب',
    memberSince: 'عضو منذ:',
    totalSessions: 'إجمالي الجلسات:',
    accountStatus: 'حالة الحساب:',
    active: 'نشط',
    quickActions: 'إجراءات سريعة',
    viewAppointments: 'عرض المواعيد',
    takeDiagnosis: 'إجراء التشخيص الذكي',
    logout: 'تسجيل الخروج',
    logoutSuccess: 'تم تسجيل الخروج بنجاح'
  },

  // Therapist Register
  therapistRegister: {
    title: 'تسجيل المعالج',
    subtitle: 'سجل كمعالج للانضمام إلى المنصة.',
    name: 'الاسم (بالعربية)',
    nameEn: 'الاسم (بالإنجليزية)',
    email: 'البريد الإلكتروني',
    phone: 'رقم الجوال',
    whatsapp: 'واتساب',
    specialty: 'التخصص / المسمى الوظيفي',
    profileImage: 'الصورة الشخصية',
    identityFront: 'هوية (الوجه الأمامي)',
    identityBack: 'هوية (الوجه الخلفي)',
    certificates: 'الشهادات',
    password: 'كلمة المرور',
    passwordConfirm: 'تأكيد كلمة المرور',
    passwordAuto: 'سيتم إنشاء كلمة مرور وإرسالها إلى بريدك الإلكتروني.',
    acceptTerms: 'أوافق على الشروط والأحكام',
    submit: 'تسجيل',
    submitting: 'جاري التسجيل...',
    success: 'تم التسجيل بنجاح! يرجى التحقق من بريدك الإلكتروني.',
    error: 'فشل التسجيل. يرجى المحاولة مرة أخرى.',
    applicationSubmitted: 'تم إرسال طلبك وهو قيد المراجعة من قبل الإدارة.'
  },

  // Language
  language: {
    ar: 'العربية',
    en: 'English',
    switchLanguage: 'تغيير اللغة'
  },

  // Not Found
  notFound: {
    title: 'الصفحة غير موجودة',
    message: 'الصفحة التي تبحث عنها غير موجودة أو تم نقلها.',
    goHome: 'الذهاب للرئيسية',
    goBack: 'العودة'
  }
} 