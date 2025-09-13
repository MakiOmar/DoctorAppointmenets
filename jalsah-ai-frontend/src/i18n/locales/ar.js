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
    hide: 'إخفاء',
    viewDetails: 'تصفح المواعيد الآخرى',
    added: 'تمت الإضافة',
    download: 'تحميل',
    upload: 'رفع',
    select: 'اختيار',
    all: 'الكل',
    none: 'لا شيء',
    required: 'مطلوب',
    optional: 'اختياري',
    unknown: 'غير معروف',
    na: 'غير متوفر',
    contact: 'تواصل',
    pleaseLogin: 'يرجى تسجيل الدخول أولاً',
    sessionExpired: 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مرة أخرى',
    minutes: 'دقيقة'
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

  // Logo
  logo: {
    text: 'جلسة'
  },

  // Home Page
  home: {
    hero: {
      title: 'رعاية نفسية ذكية ومتطورة',
      subtitle: 'احصل على تشخيص ذكي وابحث عن المعالج المناسب لك',
      cta: 'ابدأ التشخيص الذكي',
      ctaWithDiagnosis: 'تصفح المعالجين الأنسب لتشخيصك',
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
      needVerification: 'تحتاج للتحقق من حسابك؟',
      signingIn: 'جاري تسجيل الدخول...',
      signIn: 'تسجيل الدخول',
      email: 'البريد الإلكتروني',
      emailPlaceholder: 'أدخل بريدك الإلكتروني',
      whatsapp: 'رقم الواتساب',
      whatsappPlaceholder: 'أدخل رقم الواتساب',
      password: 'كلمة المرور',
      passwordPlaceholder: 'أدخل كلمة المرور',
      errors: {
        invalidCredentials: 'بيانات الدخول غير صحيحة',
        emailRequired: 'البريد الإلكتروني مطلوب',
        whatsappRequired: 'رقم الواتساب مطلوب',
        passwordRequired: 'كلمة المرور مطلوبة'
      },
      verifyAccount: 'تحقق من الحساب'
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
      firstName: 'الاسم الأول',
      lastName: 'اسم العائلة',
      age: 'العمر',
      agePlaceholder: 'عمرك',
      email: 'البريد الإلكتروني',
      emailPlaceholder: 'أدخل بريدك الإلكتروني',
      phone: 'رقم الهاتف',
      phonePlaceholder: '+1234567890',
      whatsapp: 'رقم الواتساب',
      whatsappPlaceholder: '1234567890',
      passwordMismatch: 'كلمات المرور غير متطابقة',
      errors: {
        passwordMismatch: 'كلمات المرور غير متطابقة',
        emailExists: 'البريد الإلكتروني مستخدم بالفعل',
        weakPassword: 'كلمة المرور ضعيفة جداً'
      }
    }
  },

  // Verification
  verification: {
    title: 'تحقق من رمز التأكيد',
    subtitle: 'أدخل رمز التحقق المرسل إليك',
    emailSentTo: 'تم إرسال رمز التحقق إلى:',
    whatsappSentTo: 'تم إرسال رمز التحقق إلى الواتساب:',
    code: 'رمز التحقق',
    codePlaceholder: 'أدخل الرمز المكون من 6 أرقام',
    codeHint: 'أدخل الرمز المكون من 6 أرقام المرسل إليك',
    verify: 'تحقق من الرمز',
    verifying: 'جاري التحقق...',
    didntReceive: 'لم تستلم الرمز؟',
    resendCode: 'إعادة إرسال الرمز',
    resendIn: 'إعادة الإرسال خلال {seconds} ثانية',
    sending: 'جاري الإرسال...',
    backToLogin: 'العودة لتسجيل الدخول',
    continue: 'متابعة',
    changeContact: 'تغيير جهة الاتصال'
  },

  // Toast Messages
  toast: {
    auth: {
      loginSuccess: 'تم تسجيل الدخول بنجاح!',
      loginFailed: 'فشل تسجيل الدخول',
      registerSuccess: 'تم إنشاء الحساب بنجاح! يرجى التحقق من بريدك الإلكتروني للحصول على رمز التحقق.',
      whatsappSentTo: 'تم إرسال رمز التحقق إلى الواتساب: {contact}',
      registerFailed: 'فشل إنشاء الحساب',
      userExistsVerified: 'المستخدم موجود ومتحقق بالفعل. يرجى تسجيل الدخول بدلاً من ذلك.',
      emailVerified: 'تم التحقق من البريد الإلكتروني بنجاح!',
      whatsappVerified: 'تم التحقق من الواتساب بنجاح!',
      verificationFailed: 'فشل التحقق من البريد الإلكتروني',
      verificationSent: 'تم إرسال رمز التحقق بنجاح!',
      logoutSuccess: 'تم تسجيل الخروج بنجاح',
      sessionExpired: 'انتهت صلاحية الجلسة. يرجى تسجيل الدخول مرة أخرى',
      invalidCredentials: 'بيانات الدخول غير صحيحة',
      emailRequired: 'البريد الإلكتروني مطلوب',
      passwordRequired: 'كلمة المرور مطلوبة',
      verificationRequired: 'يرجى التحقق من عنوان بريدك الإلكتروني قبل تسجيل الدخول. تحقق من بريدك الإلكتروني للحصول على رمز التحقق.'
    },
    general: {
      error: 'حدث خطأ',
      success: 'تم بنجاح',
      loading: 'جاري التحميل...',
      networkError: 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.',
      serverError: 'خطأ في الخادم. يرجى المحاولة مرة أخرى.'
    }
  },

  // Diagnosis
  diagnosis: {
    title: 'التشخيص الذكي',
    subtitle: 'أجب على بعض الأسئلة للحصول على تشخيص مخصص والعثور على المعالج المناسب لك',
    step1: {
      title: 'كيف تشعر؟',
      mood: 'المزاج الحالي',
      moodOptions: {
        happy: 'سعيد',
        neutral: 'محايد',
        sad: 'حزين',
        anxious: 'قلق',
        angry: 'غاضب',
        stressed: 'متوتر'
      },
      duration: 'منذ متى وأنت تشعر بهذا الشعور؟',
      durationOptions: {
        less_than_week: 'أقل من أسبوع',
        few_weeks: 'بضعة أسابيع',
        few_months: 'بضعة أشهر',
        six_months: 'ستة أشهر',
        year_plus: 'سنة أو أكثر'
      }
    },
    step2: {
      title: 'ما هي الأعراض التي تعاني منها؟',
      symptoms: {
        anxiety: 'قلق أو قلق',
        depression: 'اكتئاب أو حزن',
        stress: 'توتر أو إرهاق',
        sleep: 'مشاكل في النوم',
        appetite: 'تغيرات في الشهية',
        energy: 'انخفاض الطاقة أو التعب',
        concentration: 'صعوبة في التركيز',
        irritability: 'التهيج أو الغضب',
        isolation: 'العزلة الاجتماعية',
        hopelessness: 'مشاعر اليأس',
        panic: 'نوبات الهلع',
        trauma: 'أعراض الصدمة أو اضطراب ما بعد الصدمة'
      }
    },
    step3: {
      title: 'كيف يؤثر هذا على حياتك؟',
      impact: 'مستوى التأثير',
      impactOptions: {
        minimal: 'تأثير ضئيل',
        mild: 'تأثير خفيف',
        moderate: 'تأثير متوسط',
        severe: 'تأثير شديد',
        extreme: 'تأثير شديد جداً'
      },
      affectedAreas: 'ما هي مجالات حياتك المتأثرة؟',
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
      title: 'ما هي أهدافك؟',
      goals: 'ماذا تريد أن تحقق من خلال العلاج؟',
      goalsPlaceholder: 'صف أهدافك وما تأمل في تحقيقه من العلاج...',
      preferredApproach: 'هل لديك نهج علاجي مفضل؟',
      approachOptions: {
        none: 'لا تفضيل',
        cbt: 'العلاج المعرفي السلوكي',
        psychodynamic: 'العلاج النفسي الديناميكي',
        humanistic: 'العلاج الإنساني',
        mindfulness: 'العلاج القائم على اليقظة الذهنية',
        solution_focused: 'العلاج المركّز على الحلول'
      }
    },
    progress: 'الخطوة {step} من 4',
    complete: '{percent}% مكتمل',
    analyzing: 'جاري التحليل...',
    submit: 'احصل على التشخيص'
  },

  // Chat Diagnosis
  chatDiagnosis: {
    title: 'التشخيص الذكي بالدردشة',
    subtitle: 'دردش مع الذكاء الاصطناعي للحصول على تشخيص مخصص والعثور على المعالج المناسب لك',
    welcome: {
      title: 'مرحباً بك في التشخيص الذكي بالدردشة',
      description: 'أنا هنا لمساعدتك في فهم صحتك النفسية والعثور على الدعم المناسب.',
      message: 'أهلاً! أنا هنا لمساعدتك في التقييم النفسي المبدئي. هل يمكنك إخباري من أي بلد أنت؟ هذا سيساعدني في فهم سياقك الثقافي والتحدث معك باللغة المناسبة.'
    },
    input: {
      placeholder: 'اكتب رسالتك هنا...'
    },
    results: {
      title: 'اكتمل التشخيص',
      findTherapists: 'البحث عن المعالجين',
      newDiagnosis: 'بدء تشخيص جديد'
    },
    error: {
      message: 'فشل في الحصول على استجابة الذكاء الاصطناعي',
      response: 'عذراً، لدي مشكلة في معالجة رسالتك الآن. يرجى المحاولة مرة أخرى بعد لحظة.'
    },
    disclaimer: {
      title: 'تنبيه طبي',
      text: 'هذا ليس استشارة طبية. يرجى استشارة مقدم رعاية صحية مؤهل للتشخيص والعلاج المناسب.'
    },
    questionCounter: 'الأسئلة المطروحة: {count}',
    completion: {
      title: 'اكتمل التشخيص!',
      message: 'لقد أكملنا تشخيصك ونقوم الآن بإعادة توجيهك لاختيار معالج.',
      redirecting: 'جاري إعادة التوجيه لاختيار المعالج...'
    }
  },

  // Diagnosis Results
  diagnosisResults: {
    title: 'نتائج تشخيصك',
    subtitle: 'بناءً على إجاباتك، إليك ما وجدناه والمعالجون الذين يمكنهم المساعدة',
    rediagnose: 'أعد التشخيص',
    matchedTherapists: 'المعالجون المطابقون لك',
    loadingTherapists: 'البحث عن أفضل المعالجين لك...',
    noTherapistsFound: 'لم يتم العثور على معالجين',
    noTherapistsDescription: 'لم نتمكن من العثور على معالجين مطابقين خصيصاً لتشخيصك، ولكن يمكنك تصفح جميع المعالجين المتاحين.',
    errorLoadingTherapists: 'فشل في تحميل المعالجين المطابقين',
    defaultTitle: 'الدعم العام للصحة النفسية',
    defaultDescription: 'بناءً على إجاباتك، قد تستفيد من الدعم العام للصحة النفسية والعلاج.',
    showMore: 'عرض المزيد من المعالجين',
    moreTherapists: 'المزيد',
    sortBy: 'ترتيب حسب',
    sortByOrder: 'الترتيب',
    sortByPrice: 'السعر',
    sortByAppointment: 'الموعد',
    any: 'أي شيء',
    orderAsc: 'تصاعدي',
    orderDesc: 'تنازلي',
    priceLowest: 'الأقل',
    priceHighest: 'الأعلى',
    appointmentNearest: 'الأقرب',
    appointmentFarthest: 'الأبعد',
    simulatedResults: {
      anxiety: {
        title: 'اضطراب القلق',
        description: 'قد تكون تعاني من أعراض اضطراب القلق. يمكن أن يساعدك العلاج المهني في تطوير استراتيجيات التأقلم وتقليل أعراض القلق.'
      },
      depression: {
        title: 'الاكتئاب',
        description: 'تشير إجاباتك إلى أعراض الاكتئاب. يمكن أن يساعدك العلاج في فهم وإدارة هذه المشاعر بفعالية.'
      },
      stress: {
        title: 'إدارة التوتر',
        description: 'أنت تعاني من توتر كبير يؤثر على حياتك اليومية. يمكن أن يساعدك علاج إدارة التوتر في تطوير آليات تأقلم صحية.'
      },
      general: {
        title: 'الدعم النفسي',
        description: 'قد تستفيد من الدعم العام للصحة النفسية. يمكن أن يساعدك معالج مؤهل في العمل على مخاوفك وتحسين رفاهيتك.'
      }
    }
  },

  // Therapists
  therapists: {
    title: 'ابحث عن معالجك',
    subtitle: 'تصفح معالجينا المؤهلين وابحث عن المطابقة المثالية لاحتياجاتك',
    loading: 'جاري تحميل المعالجين...',
    bioDefault: 'معالج محترف متخصص في الصحة النفسية والرفاهية.',
    whyBestForDiagnosis: 'لماذا هذا المعالج هو الأفضل لتشخيصك؟',
    bookSession: 'احجز جلسة',
    viewProfile: 'عرض الملف الشخصي',
    viewDetails: 'عرض التفاصيل',
    more: '+{count} المزيد',
    noSlotsAvailable: 'تواصل للاستفسار عن التوفر',
    availableToday: 'متاح اليوم في {time}',
    availableTomorrow: 'متاح غداً في {time}',
    availableOn: 'متاح {date} في {time}',
    contactForAvailability: 'تواصل للاستفسار عن التوفر',
    specializations: 'التخصصات',
    sortBy: 'ترتيب حسب',
    sorting: {
      best: 'الأفضل',
      priceLow: 'السعر الأقل',
      nearest: 'أقرب موعد'
    },
    loadingMore: 'جاري تحميل المزيد...',
    allLoaded: 'تم تحميل جميع المعالجين',
    filters: {
      specialization: 'التخصص',
      allSpecializations: 'جميع التخصصات',
      search: 'البحث',
      searchPlaceholder: 'البحث باسم المعالج...',
      priceRange: 'نطاق السعر',
      anyPrice: 'أي سعر',
      lowestPrice: 'أقل سعر',
      highestPrice: 'أعلى سعر',
      nearestAppointment: 'أقرب موعد',
      anyTime: 'أي وقت',
      closest: 'الأقرب',
      farthest: 'الأبعد',
      sortBy: 'ترتيب حسب',
      random: 'عشوائي',
      highestRated: 'الأعلى تقييماً'
    },
    showMore: 'عرض المزيد',
    moreTherapists: 'معالجين آخرين',
    noResults: 'لم يتم العثور على معالجين',
    noResultsMessage: 'جرب تعديل معايير البحث أو تصفح جميع المعالجين'
  },

  // Therapist Detail
  therapistDetail: {
    backToTherapists: 'العودة للمعالجين',
    reviews: 'تقييمات',
    perSession: 'لجلسة مدتها 45 دقيقة',
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
    viewDetails: 'عرض التفاصيل',
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
    minutes: 'دقيقة',
    loadError: 'فشل في تحميل معلومات المعالج',
    submitSuccess: 'تم إرسال الحجز بنجاح!',
    submitError: 'فشل في إرسال الحجز. يرجى المحاولة مرة أخرى.',
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
    proceedToPayment: 'المتابعة للدفع',
    addMoreBookings: 'إضافة مواعيد أخرى',
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

  // Cart page specific translations
  shoppingCart: 'سلة التسوق',
  cartDescription: 'راجع مواعيدك المختارة وأكمل عملية الحجز',
  loadingCart: 'جاري تحميل السلة...',
  errorLoadingCart: 'خطأ في تحميل السلة',
  emptyCart: 'السلة فارغة',
  emptyCartDescription: 'لم تقم بإضافة أي مواعيد إلى سلتك بعد',
  subtotal: 'المجموع الفرعي',
  minutes: 'دقيقة',
  appointments: 'المواعيد',
  orderSummary: 'ملخص الطلب',
  total: 'المجموع',
  proceedToCheckout: 'المتابعة للدفع',
  proceedToPayment: 'المتابعة للدفع',
  remove: 'إزالة',
  duration: 'المدة',

  // Checkout page translations
  checkout: 'إتمام الحجز',
  completeYourBooking: 'أكمل عملية الحجز الخاصة بك',
  loadingCheckout: 'جاري تحميل صفحة الدفع...',
  errorLoadingCheckout: 'خطأ في تحميل صفحة الدفع',
  orderCreated: 'تم إنشاء الطلب بنجاح!',
  redirectingToPayment: 'جاري توجيهك إلى صفحة الدفع...',
  paymentMethod: 'طريقة الدفع',
  cashPayment: 'الدفع نقداً',
  cardPayment: 'الدفع بالبطاقة',
  paymentSummary: 'ملخص الدفع',
  completePayment: 'إتمام الدفع',
  backToCart: 'العودة إلى السلة',

  // Appointments Page
  appointmentsPage: {
    title: 'مواعيدي',
    loading: 'جاري تحميل المواعيد...',
    booking: 'جاري الحجز...',
    tabs: {
      upcoming: 'القادمة',
      past: 'السابقة',
      cancelled: 'الملغية'
    },
    date: 'التاريخ',
    time: 'الوقت',
    duration: 'المدة',
    status: 'الحالة',
    notes: 'ملاحظات',
    joinSession: 'انضم للجلسة',
    reschedule: 'إعادة جدولة',
    cancel: 'إلغاء',
    viewDetails: 'عرض التفاصيل',
    bookWithSameTherapist: 'حجز جلسة جديدة مع نفس المعالج',
    sessionLinkAvailable: 'رابط الجلسة متاح',
    sessionLinkMessage: 'انقر على الرابط أدناه للانضمام إلى جلستك',
    joinNow: 'انضم الآن',
    noAppointments: 'لا توجد مواعيد',
    noUpcoming: 'ليس لديك مواعيد قادمة.',
    noPast: 'ليس لديك مواعيد سابقة.',
    noCancelled: 'ليس لديك مواعيد ملغية.',
    bookSession: 'حجز جلسة',
    cancelTitle: 'إلغاء الموعد',
    cancelMessage: 'هل أنت متأكد من أنك تريد إلغاء هذا الموعد؟ لا يمكن التراجع عن هذا الإجراء.',
    cancelling: 'جاري الإلغاء...',
    yesCancel: 'نعم، إلغاء',
    noKeep: 'لا، احتفظ',
    // Status values
    statusConfirmed: 'مؤكد',
    statusPending: 'في الانتظار',
    statusCompleted: 'مكتمل',
    statusCancelled: 'ملغي',
    statusNoShow: 'لم يحضر',
    // Time units
    minutes: 'دقيقة',
    // Additional fields
    therapist: 'المعالج',
    session: 'الجلسة'
  },

  // Therapist Appointment Page
  therapistAppointment: {
    title: 'حجز موعد',
    subtitle: 'اختر موعداً لحجز جلستك',
    bookSession: 'حجز الجلسة',
    bookNow: 'احجز الآن',
    booking: 'جاري الحجز...',
    addedToCart: 'تم الإضافة إلى السلة بنجاح',
    bookingError: 'خطأ في حجز الموعد',
    loadError: 'خطأ في تحميل معلومات المعالج',
    noSlotsAvailable: 'لا توجد مواعيد متاحة في الوقت الحالي'
  },

  // Reschedule Page
  reschedule: {
    title: 'إعادة جدولة الموعد',
    subtitle: 'اختر تاريخ ووقت جديدين لموعدك',
    currentAppointment: 'الموعد الحالي',
    therapist: 'المعالج',
    currentDate: 'التاريخ الحالي',
    currentTime: 'الوقت الحالي',
    duration: 'المدة',
    selectNewTime: 'اختر وقت جديد',
    selectDate: 'اختر التاريخ',
    selectTime: 'اختر الوقت',
    noSlotsAvailable: 'لا توجد أوقات متاحة لهذا التاريخ',
    confirmReschedule: 'تأكيد إعادة الجدولة',
    rescheduling: 'جاري إعادة الجدولة...',
    success: 'تمت إعادة جدولة الموعد بنجاح',
    error: 'فشل في إعادة جدولة الموعد',
    alreadyRescheduled: 'تمت إعادة جدولة هذا الموعد مرة واحدة بالفعل',
    alreadyRescheduledTitle: 'تمت إعادة الجدولة مسبقاً',
    alreadyRescheduledMessage: 'تمت إعادة جدولة هذا الموعد مرة واحدة بالفعل. لا يُسمح بإعادة الجدولة المتعددة.',
    cannotReschedule: 'لا يمكن إعادة الجدولة',
    leaveMeetingTitle: 'مغادرة الجلسة؟',
    leaveMeetingMessage: 'هل أنت متأكد من أنك تريد مغادرة الجلسة؟ سيتم توجيهك إلى مواعيدك.',
    leaveMeeting: 'مغادرة الجلسة',
    stayInMeeting: 'البقاء في الجلسة',
    prescription: 'روشتة',
    prescriptionRequested: 'تم طلب الروشتة',
    prescriptionServices: 'خدمات الروشتة',
    bookFreeAppointment: 'احجز موعد مجاني',
    bookAppointment: 'احجز موعد',
    confirmBooking: 'تأكيد الحجز',
    confirmBookingMessage: 'هل أنت متأكد من أنك تريد حجز هذا الموعد؟',
    noAvailableSlots: 'لا توجد مواعيد متاحة حالياً',
    confirmMedication: 'هل تعتقد أن المريض يحتاج إلى دواء وتريد إحالته إلى طبيب نفسي (مجاناً) لوصف الدواء مع جلساتك؟',
    preliminaryDiagnosis: 'التشخيص الأولي للمريض حسب ملاحظتك',
    symptoms: 'الأعراض التي تعتقد أنها تحتاج إلى دواء',
    request: 'طلب',
    cancel: 'إلغاء',
    yes: 'نعم',
    no: 'لا',
    close: 'إغلاق',
    successMessage: 'تم تقديم طلب خدمة الروشتة بنجاح.',
    errorMessage: 'حدث خطأ. يرجى المحاولة مرة أخرى.',
    reasonForReferral: 'سبب الإحالة',
    pending: 'قيد الانتظار',
    confirmed: 'مؤكد',
    viewAppointment: 'عرض الموعد'
  },

  // Prescription
  prescription: {
    prescriptionServices: 'خدمات الروشتة',
    prescriptionRequested: 'تم طلب الروشتة',
    completedPrescriptions: 'الروشتات الخاصة بي',
    bookFreeAppointment: 'احجز موعد مجاني',
    bookAppointment: 'احجز موعد',
    confirmBooking: 'تأكيد الحجز',
    confirmBookingMessage: 'هل أنت متأكد من أنك تريد حجز هذا الموعد؟',
    joinMeeting: 'انضم للجلسة',
    rochtahSession: 'جلسة رشتة',
    roomName: 'اسم الغرفة',
    startMeeting: 'ابدأ الجلسة',
    pending: 'قيد الانتظار',
    confirmed: 'مؤكد',
    viewAppointment: 'عرض الموعد',
    viewPrescription: 'عرض الروشتة',
    prescriptionDetails: 'تفاصيل الروشتة',
    prescribedBy: 'وصفت من قبل',
    prescribedAt: 'تاريخ الوصف',
    medications: 'الأدوية',
    dosageInstructions: 'تعليمات الجرعة',
    doctorNotes: 'ملاحظات الطبيب',
    initialDiagnosis: 'التشخيص الأولي',
    symptoms: 'الأعراض',
    reasonForReferral: 'سبب الإحالة',
    prescriptionText: 'الروشتة',
    noPrescriptions: 'لا توجد روشتات متاحة',
    loadingPrescriptions: 'جاري تحميل الروشتات...'
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
    contactName: 'اسم جهة الاتصال',
    contactPhone: 'هاتف جهة الاتصال',
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
    takeDiagnosis: 'إجراء تشخيص ذكي',
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

  // Therapist Details
  therapistDetails: {
    title: 'تفاصيل المعالج',
    loading: 'جاري تحميل التفاصيل...',
    error: 'خطأ في تحميل التفاصيل',
    loadError: 'فشل في تحميل التفاصيل. يرجى المحاولة مرة أخرى.',
    bio: 'السيرة الذاتية',
    noCertificates: 'لا توجد شهادات متاحة',
    noCertificatesMessage: 'لم يقم هذا المعالج برفع أي شهادات بعد.',
    noDescription: 'لا يوجد وصف متاح',
    personalInfo: 'المعلومات الشخصية',
    name: 'الاسم',
    nameEn: 'الاسم (بالإنجليزية)',
    specialty: 'التخصص',
    jalsahAiName: 'اسم جلسة الذكي',
    email: 'البريد الإلكتروني',
    phone: 'الهاتف',
    whatsapp: 'واتساب',
    applicationInfo: 'معلومات الطلب',
    applicationDate: 'تاريخ التقديم',
    approvalDate: 'تاريخ الموافقة',
    certificatesCount: 'الشهادات',
    certificates: 'الشهادات',
    downloadFile: 'تحميل الملف',
    bookAppointment: 'حجز موعد',
    earliestAvailable: 'أقرب موعد متاح',
    bookThis: 'احجز هذا',
    bookAnother: 'احجز موعد آخر',
    loadingDates: 'جاري تحميل التواريخ المتاحة...',
    availableTimes: 'الأوقات المتاحة',
    noTimeSlots: 'لا توجد أوقات متاحة لهذا التاريخ',
    noAvailableDates: 'لا توجد تواريخ متاحة',
    appointmentAdded: 'تم إضافة الموعد إلى السلة بنجاح. سيتم إزالة الموعد تلقائياً بعد نصف ساعة إذا لم يتم إتمام الدفع.',
    appointmentRemoved: 'تم إزالة الموعد من السلة بنجاح',
    differentTherapistTitle: 'معالج مختلف',
    differentTherapistMessage: 'لديك مواعيد من معالج آخر في السلة. إضافة هذا الموعد سيقوم بتفريغ السلة للحجز مع معالج مختلف.',
    inCart: 'في السلة',
    viewAllCertificates: 'عرض جميع الشهادات'
  },

  // Certificates
  certificates: {
    title: 'الشهادات والمؤهلات',
    loading: 'جاري تحميل الشهادات...',
    error: 'خطأ في تحميل الشهادات',
    loadError: 'فشل في تحميل الشهادات. يرجى المحاولة مرة أخرى.',
    noCertificates: 'لا توجد شهادات متاحة',
    noCertificatesMessage: 'لم يقم هذا المعالج برفع أي شهادات بعد.',
    noDescription: 'لا يوجد وصف متاح'
  },

  // Language
  language: {
    ar: 'العربية',
    en: 'English',
    switchLanguage: 'تغيير اللغة'
  },

  // Date and Time
  dateTime: {
    am: 'ص',
    pm: 'م',
    at: 'في',
    months: {
      january: 'يناير',
      february: 'فبراير',
      march: 'مارس',
      april: 'أبريل',
      may: 'مايو',
      june: 'يونيو',
      july: 'يوليو',
      august: 'أغسطس',
      september: 'سبتمبر',
      october: 'أكتوبر',
      november: 'نوفمبر',
      december: 'ديسمبر'
    },
    monthsShort: {
      jan: 'ينا',
      feb: 'فبر',
      mar: 'مار',
      apr: 'أبر',
      may: 'ماي',
      jun: 'يون',
      jul: 'يول',
      aug: 'أغس',
      sep: 'سبت',
      oct: 'أكت',
      nov: 'نوف',
      dec: 'ديس'
    },
    days: {
      sunday: 'الأحد',
      monday: 'الاثنين',
      tuesday: 'الثلاثاء',
      wednesday: 'الأربعاء',
      thursday: 'الخميس',
      friday: 'الجمعة',
      saturday: 'السبت'
    },
    daysShort: {
      sun: 'أحد',
      mon: 'اثن',
      tue: 'ثلا',
      wed: 'أرب',
      thu: 'خمي',
      fri: 'جمع',
      sat: 'سبت'
    }
  },

  // Session Management
  session: {
    title: 'الجلسة',
    with: 'مع',
    loading: 'جاري تحميل الجلسة...',
    error: 'خطأ في تحميل الجلسة',
    notFound: 'الجلسة غير موجودة',
    notFoundDescription: 'الجلسة التي تبحث عنها غير موجودة أو ليس لديك صلاحية للوصول إليها.',
    backToAppointments: 'العودة للمواعيد',
    therapist: 'المعالج',
    time: 'وقت الجلسة',
    duration: 'المدة',
    minutes: 'دقيقة',
    actions: 'إجراءات الجلسة',
    join: 'انضم للجلسة',
    joining: 'جاري الانضمام...',
    joinError: 'فشل في الانضمام للجلسة',
    joined: 'تم الانضمام للجلسة بنجاح',
    startMeeting: 'ابدأ الجلسة',
    starting: 'جاري بدء الجلسة...',
    meetingStarted: 'تم بدء الجلسة بنجاح',
    startError: 'فشل في بدء الجلسة',
    end: 'إنهاء الجلسة',
    ending: 'جاري الإنهاء...',
    endError: 'fشل في إنهاء الجلسة',
    ended: 'تم إنهاء الجلسة بنجاح',
    confirmEnd: 'هل أنت متأكد من إنهاء هذه الجلسة؟',
    confirmEndAbsent: 'هل أنت متأكد من إنهاء هذه الجلسة وتحديد المريض كمتغيب؟',
    confirmEndTitle: 'إنهاء الجلسة',
    confirmEndYes: 'نعم، أنهِ الجلسة',
    confirmEndNo: 'إلغاء',
    endSessionTitle: 'إنهاء الجلسة',
    markCompletedTitle: 'تحديد كمكتملة',
    markCompleted: 'تحديد الجلسة كمكتملة',
    marking: 'جاري التحديد كمكتملة...',
    markedCompleted: 'تم تحديد الجلسة كمكتملة بنجاح',
    markCompletedError: 'فشل في تحديد الجلسة كمكتملة',
    confirmMarkCompletedTitle: 'تحديد الجلسة كمكتملة',
    confirmMarkCompleted: 'هل أنت متأكد من تحديد هذه الجلسة كمكتملة؟',
    confirmMarkCompletedYes: 'نعم، حدد كمكتملة',
    confirmMarkCompletedNo: 'إلغاء',
    patientAttended: 'حضر المريض الجلسة',
    patientAbsent: 'تغيب المريض عن الجلسة',
    absenceWarning: 'قاعدة الـ 15 دقيقة',
    wait15Minutes: 'يجب الانتظار لمدة 15 دقيقة على الأقل بعد وقت بدء الجلسة قبل تحديد المريض كمتغيب.',
    waitingForTherapist: 'في انتظار المعالج...',
    waitingDescription: 'يرجى الانتظار حتى ينضم معالجك للجلسة.',
    notAvailable: 'الجلسة غير متاحة',
    meetingRoom: 'غرفة الاجتماع',
    timeExpired: 'انتهى وقت الجلسة',
    loadingMeeting: 'جاري تحميل غرفة الاجتماع...',
    meetingError: 'حدث خطأ في تحميل غرفة الاجتماع',
    connecting: 'جاري الاتصال...',
    showMeeting: 'عرض الاجتماع',
    instructions: 'تعليمات الجلسة',
    instruction1: 'تأكد من وجود اتصال إنترنت مستقر',
    instruction2: 'ابحث عن مكان هادئ وخاص لجلستك',
    instruction3: 'احضر الكاميرا والميكروفون',
    loadError: 'فشل في تحميل تفاصيل الجلسة',
    status: {
      pending: 'في الانتظار',
      confirmed: 'مؤكدة',
      completed: 'مكتملة',
      cancelled: 'ملغية',
      noShow: 'لم يحضر'
    },
    reason: {
      notConfirmed: 'الجلسة لم تؤكد بعد',
      tooEarly: 'الجلسة لم تبدأ بعد',
      tooLate: 'انتهى وقت الجلسة',
      notAuthorized: 'ليس لديك صلاحية للانضمام لهذه الجلسة',
      completed: 'انتهت الجلسة',
      cancelled: 'تم إلغاء الجلسة',
      unknown: 'الجلسة غير متاحة في هذا الوقت'
    }
  },

  // Not Found
  notFound: {
    title: 'الصفحة غير موجودة',
    message: 'الصفحة التي تبحث عنها غير موجودة أو تم نقلها.',
    goHome: 'الذهاب للرئيسية',
    goBack: 'العودة'
  },

  // Appointment Change Terms
  appointmentChangeTerms: 'شروط تغيير الموعد'
} 