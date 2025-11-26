/**
 * Currency formatting utilities
 */

/**
 * Format price with Egyptian Pound symbol
 * @param {number} amount - The amount to format
 * @param {string} locale - The locale (ar/en)
 * @returns {string} Formatted price
 */
export function formatPrice(amount, locale = 'en') {
  if (amount === null || amount === undefined || isNaN(amount)) {
    return '0 ج.م'
  }
  
  const numAmount = parseFloat(amount)
  
  if (locale === 'ar') {
    // Arabic format: 1,234.56 ج.م
    return `${numAmount.toLocaleString('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    })} ج.م`
  } else {
    // English format: 1,234.56 ج.م
    return `${numAmount.toLocaleString('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    })} ج.م`
  }
}

/**
 * Format price without symbol (just the number)
 * @param {number} amount - The amount to format
 * @returns {string} Formatted number
 */
export function formatAmount(amount) {
  if (amount === null || amount === undefined || isNaN(amount)) {
    return '0'
  }
  
  const numAmount = parseFloat(amount)
  return numAmount.toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  })
}

/**
 * Get currency symbol
 * @returns {string} Currency symbol
 */
export function getCurrencySymbol() {
  return 'ج.م'
} 