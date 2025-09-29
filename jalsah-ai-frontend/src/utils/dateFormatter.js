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
  
  // Force Gregorian calendar by using specific locale settings
  const gregorianOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    calendar: 'gregory', // Explicitly force Gregorian calendar
    ...options
  }
  
  // Use English locale for date formatting to ensure Gregorian
  // but keep the month names in the appropriate language
  const formatLocale = locale === 'ar' ? 'en-US' : 'en-US'
  
  try {
    return date.toLocaleDateString(formatLocale, gregorianOptions)
  } catch (error) {
    // Fallback to manual formatting if locale options fail
    return formatDateManually(date, locale)
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
 * Manual date formatting fallback
 * @param {Date} date - Date object
 * @param {string} locale - Current app locale
 * @returns {string} Manually formatted date
 */
const formatDateManually = (date, locale) => {
  const year = date.getFullYear()
  const month = date.getMonth() + 1
  const day = date.getDate()
  
  if (locale === 'ar') {
    return `${day}/${month}/${year}`
  }
  
  return `${month}/${day}/${year}`
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
    return `${day}/${month}/${year} ${hours}:${minutes}`
  }
  
  return `${month}/${day}/${year} ${hours}:${minutes}`
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
    return `${day}/${month}/${year}`
  }
  
  return `${month}/${day}/${year}`
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
