/**
 * Date formatting utilities that ensure Gregorian calendar display
 * regardless of browser locale settings
 */

/**
 * Format date to Gregorian format with locale-appropriate display
 * @param {string|Date} dateString - Date string or Date object
 * @param {string} locale - Current app locale ('ar' or 'en')
 * @param {object} options - Formatting options
 * @returns {string} Formatted date string
 */
export const formatGregorianDate = (dateString, locale = 'en', options = {}) => {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return 'N/A'
  
  // For Arabic locale, use manual formatting to ensure Arabic month names with Gregorian calendar
  if (locale === 'ar') {
    return formatDateManually(date, locale, options)
  }
  
  // For English locale, use standard formatting
  const gregorianOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    calendar: 'gregory', // Explicitly force Gregorian calendar
    ...options
  }
  
  try {
    return date.toLocaleDateString('en-US', gregorianOptions)
  } catch (error) {
    // Fallback to manual formatting if locale options fail
    return formatDateManually(date, locale, options)
  }
}

/**
 * Format date with time (Gregorian)
 * @param {string|Date} dateString - Date string or Date object
 * @param {string} locale - Current app locale
 * @returns {string} Formatted date and time string
 */
export const formatGregorianDateTime = (dateString, locale = 'en') => {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return 'N/A'
  
  const options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    calendar: 'gregory'
  }
  
  const formatLocale = locale === 'ar' ? 'en-US' : 'en-US'
  
  try {
    return date.toLocaleDateString(formatLocale, options)
  } catch (error) {
    return formatDateTimeManually(date, locale)
  }
}

/**
 * Format date for short display (Gregorian)
 * @param {string|Date} dateString - Date string or Date object
 * @param {string} locale - Current app locale
 * @returns {string} Short formatted date string
 */
export const formatGregorianDateShort = (dateString, locale = 'en') => {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return 'N/A'
  
  const options = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    calendar: 'gregory'
  }
  
  const formatLocale = locale === 'ar' ? 'en-US' : 'en-US'
  
  try {
    return date.toLocaleDateString(formatLocale, options)
  } catch (error) {
    return formatDateShortManually(date, locale)
  }
}

/**
 * Manual date formatting fallback with proper Arabic month names
 * @param {Date} date - Date object
 * @param {string} locale - Current app locale
 * @param {object} options - Formatting options
 * @returns {string} Manually formatted date
 */
const formatDateManually = (date, locale, options = {}) => {
  const year = date.getFullYear()
  const month = date.getMonth() + 1
  const day = date.getDate()
  const dayOfWeek = date.getDay()
  
  if (locale === 'ar') {
    // Arabic month names (Gregorian calendar)
    const arabicMonths = [
      'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
      'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
    ]
    
    // Arabic day names
    const arabicDays = [
      'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'
    ]
    
    const monthName = arabicMonths[date.getMonth()]
    const dayName = arabicDays[dayOfWeek]
    
    // Check if we need weekday in the format
    if (options.weekday === 'long') {
      return `${dayName}، ${day} ${monthName} ${year}`
    } else if (options.weekday === 'short') {
      const shortDays = ['أحد', 'إثن', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت']
      return `${shortDays[dayOfWeek]}، ${day} ${monthName}`
    } else if (options.month === 'short') {
      const shortMonths = [
        'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
      ]
      return `${day} ${shortMonths[date.getMonth()]}`
    } else {
      return `${day} ${monthName} ${year}`
    }
  }
  
  // English formatting
  if (options.weekday === 'long') {
    const englishDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
    const englishMonths = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December']
    return `${englishDays[dayOfWeek]}, ${englishMonths[date.getMonth()]} ${day}, ${year}`
  } else if (options.weekday === 'short') {
    const shortDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    return `${shortDays[dayOfWeek]}, ${shortMonths[date.getMonth()]} ${day}`
  } else if (options.month === 'short') {
    const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    return `${shortMonths[date.getMonth()]} ${day}`
  } else {
    return `${month}/${day}/${year}`
  }
}

/**
 * Manual date and time formatting fallback
 * @param {Date} date - Date object
 * @param {string} locale - Current app locale
 * @returns {string} Manually formatted date and time
 */
const formatDateTimeManually = (date, locale) => {
  const year = date.getFullYear()
  const month = date.getMonth() + 1
  const day = date.getDate()
  const hours = date.getHours().toString().padStart(2, '0')
  const minutes = date.getMinutes().toString().padStart(2, '0')
  
  if (locale === 'ar') {
    // Arabic month names (Gregorian calendar)
    const arabicMonths = [
      'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
      'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
    ]
    const monthName = arabicMonths[date.getMonth()]
    return `${day} ${monthName} ${year} ${hours}:${minutes}`
  }
  
  // English formatting
  const englishMonths = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December']
  const monthName = englishMonths[date.getMonth()]
  return `${monthName} ${day}, ${year} ${hours}:${minutes}`
}

/**
 * Manual short date formatting fallback
 * @param {Date} date - Date object
 * @param {string} locale - Current app locale
 * @returns {string} Manually formatted short date
 */
const formatDateShortManually = (date, locale) => {
  const year = date.getFullYear()
  const month = (date.getMonth() + 1).toString().padStart(2, '0')
  const day = date.getDate().toString().padStart(2, '0')
  
  if (locale === 'ar') {
    // Arabic short month names (Gregorian calendar)
    const shortArabicMonths = [
      'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
      'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
    ]
    const monthName = shortArabicMonths[date.getMonth()]
    return `${day} ${monthName}`
  }
  
  // English short formatting
  const shortEnglishMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                             'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  const monthName = shortEnglishMonths[date.getMonth()]
  return `${monthName} ${day}`
}

/**
 * Get relative time (e.g., "2 days ago") in Gregorian context
 * @param {string|Date} dateString - Date string or Date object
 * @param {string} locale - Current app locale
 * @returns {string} Relative time string
 */
export const getRelativeTime = (dateString, locale = 'en') => {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffInSeconds = Math.floor((now - date) / 1000)
  
  if (diffInSeconds < 60) {
    return locale === 'ar' ? 'الآن' : 'Just now'
  }
  
  const diffInMinutes = Math.floor(diffInSeconds / 60)
  if (diffInMinutes < 60) {
    return locale === 'ar' ? `منذ ${diffInMinutes} دقيقة` : `${diffInMinutes} minutes ago`
  }
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) {
    return locale === 'ar' ? `منذ ${diffInHours} ساعة` : `${diffInHours} hours ago`
  }
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 30) {
    return locale === 'ar' ? `منذ ${diffInDays} يوم` : `${diffInDays} days ago`
  }
  
  // For longer periods, show the actual date
  return formatGregorianDate(dateString, locale)
}
