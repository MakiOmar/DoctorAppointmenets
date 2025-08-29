import { useI18n } from 'vue-i18n'

export const formatDate = (dateString, locale = 'en') => {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  
  if (locale === 'ar') {
    // Arabic month names
    const arabicMonths = [
      'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
      'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
    ]
    
    // Arabic day names
    const arabicDays = [
      'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'
    ]
    
    const dayName = arabicDays[date.getDay()]
    const monthName = arabicMonths[date.getMonth()]
    const day = date.getDate()
    const year = date.getFullYear()
    
    return `${dayName}، ${day} ${monthName} ${year}`
  } else {
    // English formatting
    return date.toLocaleDateString('en-US', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
}

export const formatTime = (timeString, locale = 'en') => {
  if (!timeString) return 'N/A'
  
  const [hours, minutes] = timeString.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? (locale === 'ar' ? 'م' : 'PM') : (locale === 'ar' ? 'ص' : 'AM')
  const displayHour = hour % 12 || 12
  return `${displayHour}:${minutes} ${ampm}`
}

export const formatDateTime = (dateTimeString, locale = 'en') => {
  if (!dateTimeString) return 'N/A'
  
  const date = new Date(dateTimeString)
  const dateStr = formatDate(date, locale)
  const timeStr = formatTime(date.toTimeString().slice(0, 5), locale)
  
  return `${dateStr} - ${timeStr}`
}
