import { formatGregorianDate } from './dateFormatter'

export const formatDate = (dateString, locale = 'en') => {
  return formatGregorianDate(dateString, locale, {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

export const formatTime = (timeString, locale = 'en') => {
  if (!timeString) return 'N/A'
  
  // Handle both "09:00" and "09:00:00" formats
  const timeParts = timeString.split(':')
  const hours = parseInt(timeParts[0])
  const minutes = parseInt(timeParts[1])
  
  if (isNaN(hours) || isNaN(minutes)) {
    return 'N/A'
  }
  
  const ampm = hours >= 12 ? (locale === 'ar' ? 'م' : 'PM') : (locale === 'ar' ? 'ص' : 'AM')
  const displayHour = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
  const formattedMinutes = minutes.toString().padStart(2, '0')
  
  return `${displayHour}:${formattedMinutes} ${ampm}`
}

export const formatDateTime = (dateTimeString, locale = 'en') => {
  if (!dateTimeString) return 'N/A'
  
  const date = new Date(dateTimeString)
  const dateStr = formatDate(date, locale)
  const timeStr = formatTime(date.toTimeString().slice(0, 5), locale)
  
  return `${dateStr} - ${timeStr}`
}
