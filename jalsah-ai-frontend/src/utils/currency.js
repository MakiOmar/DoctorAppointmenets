/**
 * Currency formatting utilities
 */

/**
 * Format price with currency symbol
 * @param {number} amount - The amount to format
 * @param {string} locale - The locale (ar/en)
 * @param {string} currency - The currency symbol (e.g., 'ج.م', 'ر.س', 'د.إ')
 * @returns {string} Formatted price
 */
export function formatPrice(amount, locale = 'en', currency = null) {
  if (amount === null || amount === undefined || isNaN(amount)) {
    return currency ? `0 ${currency}` : '0 ج.م'
  }
  
  const numAmount = parseFloat(amount)
  const currencySymbol = currency || 'ج.م'
  
  // Format number with locale
  const formattedNumber = numAmount.toLocaleString('en-US', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
  })
  
  return `${formattedNumber} ${currencySymbol}`
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
 * Get currency symbol by currency code
 * @param {string} currencyCode - Currency code (e.g., 'EGP', 'GBP', 'EUR')
 * @returns {string} Currency symbol (e.g., 'ج.م', 'GBP', '€')
 */
export function getCurrencySymbol(currencyCode = null) {
  // Map currency codes to symbols (same as backend acrsw_currency function)
  const currencySymbolMap = {
    'EGP': 'ج.م',
    'SAR': 'ر.س',
    'AED': 'د.إ',
    'KWD': 'د.ك',
    'QAR': 'ر.ق',
    'BHD': 'د.ب',
    'OMR': 'ر.ع',
    'EUR': '€',
    'USD': 'USD',
    'GBP': 'GBP',
    'CAD': 'CAD',
    'AUD': 'AUD'
  }
  
  if (currencyCode) {
    const code = currencyCode.toUpperCase()
    return currencySymbolMap[code] || code // Return code itself if not found
  }
  
  return 'ج.م' // Default to Egyptian Pound
} 