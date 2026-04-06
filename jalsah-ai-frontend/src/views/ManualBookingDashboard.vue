<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="font-jalsah1 mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-primary-500 mb-6">
      {{ $t('manualBooking.title') }}
    </h1>

    <!-- Tabs: horizontal scroll on mobile so all tabs stay in one row -->
    <div class="max-w-4xl mx-auto mb-6 -mx-4 px-4 sm:mx-0 sm:px-0 overflow-x-auto border-b border-gray-200 manual-booking-tabs-scroll">
      <div class="flex flex-nowrap gap-0 min-w-max sm:min-w-0">
        <button
          type="button"
          class="px-3 py-3 sm:px-4 font-medium text-sm sm:text-base whitespace-nowrap shrink-0 border-b-2 transition-colors"
          :class="activeTab === 'new' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'new'"
        >
          {{ $t('manualBooking.newBooking') }}
        </button>
        <button
          type="button"
          class="px-3 py-3 sm:px-4 font-medium text-sm sm:text-base whitespace-nowrap shrink-0 border-b-2 transition-colors"
          :class="activeTab === 'change' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'change'"
        >
          {{ $t('manualBooking.changeAppointment') }}
        </button>
        <button
          type="button"
          class="px-3 py-3 sm:px-4 font-medium text-sm sm:text-base whitespace-nowrap shrink-0 border-b-2 transition-colors"
          :class="activeTab === 'manage' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'manage'; loadManageBookings()"
        >
          {{ $t('manualBooking.manageBookings') }}
        </button>
        <button
          type="button"
          class="px-3 py-3 sm:px-4 font-medium text-sm sm:text-base whitespace-nowrap shrink-0 border-b-2 transition-colors"
          :class="activeTab === 'openSlots' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'openSlots'"
        >
          {{ $t('manualBooking.openSlots') }}
        </button>
        <button
          type="button"
          class="px-3 py-3 sm:px-4 font-medium text-sm sm:text-base whitespace-nowrap shrink-0 border-b-2 transition-colors"
          :class="activeTab === 'searchByPhone' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'searchByPhone'"
        >
          {{ $t('manualBooking.searchByPhone') }}
        </button>
        <button
          type="button"
          class="px-3 py-3 sm:px-4 font-medium text-sm sm:text-base whitespace-nowrap shrink-0 border-b-2 transition-colors"
          :class="activeTab === 'availabilityCopy' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'availabilityCopy'"
        >
          {{ $t('manualBooking.availabilityCopy') }}
        </button>
      </div>
    </div>

    <!-- New booking -->
    <form v-if="activeTab === 'new'" class="space-y-4 max-w-2xl mx-auto" @submit.prevent="submitNewBooking">
      <!-- Patient: country + phone (stack search button on mobile for cleaner layout) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.enterPhone') }}</label>
        <div class="flex flex-col gap-2 sm:flex-row sm:gap-0">
          <div class="relative flex flex-1 min-w-0 rounded-md shadow-sm">
            <button
              type="button"
              class="inline-flex items-center px-3 rounded-l-md border border-gray-300 bg-gray-50 text-sm shrink-0"
              @click="showPatientCountryDropdown = !showPatientCountryDropdown"
            >
              <span class="mr-1">{{ selectedPatientCountry?.flag }}</span>
              <span>{{ selectedPatientCountry?.dial_code ?? '+20' }}</span>
            </button>
            <div v-if="showPatientCountryDropdown" class="absolute z-20 mt-9 left-0 w-56 bg-white border rounded shadow-lg max-h-48 overflow-y-auto">
              <input
                v-model="patientCountrySearch"
                type="text"
                :placeholder="$t('manualBooking.searchCountries')"
                class="w-full px-2 py-1 border-b text-sm"
              />
              <button
                v-for="c in filteredPatientCountries"
                :key="c.country_code"
                type="button"
                class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 flex items-center"
                @click="selectPatientCountry(c); showPatientCountryDropdown = false"
              >
                <span class="mr-2">{{ c.flag }}</span>
                <span>{{ $i18n.locale === 'ar' && c.name_ar ? c.name_ar : (c.name_en || c.name) }}</span>
                <span class="text-gray-400 text-xs ml-1">{{ c.dial_code }}</span>
              </button>
            </div>
            <input
              v-model="patientPhoneDigits"
              type="text"
              inputmode="numeric"
              class="flex-1 min-w-0 rounded-r-md border border-gray-300 px-3 py-2.5 sm:py-2"
              :class="{ 'border-red-500': errors?.phone }"
              :placeholder="$t('manualBooking.phoneDigits')"
              @input="onPatientPhoneInput"
              @blur="onPatientPhoneBlur"
            />
          </div>
          <div class="flex items-center gap-2 sm:ml-2 shrink-0">
            <button
              type="button"
              class="w-full sm:w-auto px-4 py-2.5 sm:py-2 rounded-md border border-primary-500 bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed min-h-[42px] sm:min-h-0"
              :disabled="patientSearchLoading || !patientPhoneDigits.trim()"
              @click="runPatientSearch"
            >
              {{ $t('manualBooking.searchPatient') || 'Search' }}
            </button>
            <span v-if="patientSearchLoading" class="animate-spin h-5 w-5 border-2 border-primary-500 border-t-transparent rounded-full shrink-0" />
          </div>
        </div>
        <p v-if="errors?.phone" class="mt-1 text-sm text-red-600">{{ errors?.phone }}</p>
        <!-- Patient search results -->
        <div v-if="patientSearchResults.length > 0" class="mt-1 border rounded bg-white shadow-lg max-h-40 overflow-y-auto">
          <button
            v-for="p in patientSearchResults"
            :key="p.id"
            type="button"
            class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
            @click="selectPatient(p)"
          >
            <template v-if="p.is_new">
              {{ 'إنشاء مريض جديد' }} - {{ p.name || p.email }}
            </template>
            <template v-else>
              {{ p.name || p.email }} {{ p.first_name || p.last_name ? `(${p.first_name} ${p.last_name})` : '' }}
            </template>
          </button>
        </div>
      </div>

      <!-- Full name -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.firstName') }} *</label>
        <input v-model="patientFirstName" type="text" class="w-full rounded border px-3 py-2" :class="errors?.firstName ? 'border-red-500' : 'border-gray-300'" />
        <p v-if="errors?.firstName" class="mt-1 text-sm text-red-600">{{ errors?.firstName }}</p>
      </div>

      <!-- Therapist: searchable select (search inside dropdown, same as dashboard) -->
      <div ref="therapistDropdownRef">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.therapist') }}</label>
        <div class="flex gap-2 items-stretch">
          <div class="relative flex-1">
            <button
              type="button"
              class="w-full rounded border px-3 py-2 text-left flex items-center justify-between min-h-[42px]"
              :class="errors?.therapist ? 'border-red-500' : 'border-gray-300'"
              @click="showTherapistDropdown = !showTherapistDropdown"
            >
              <span v-if="selectedTherapistDisplay" class="truncate">{{ selectedTherapistDisplay }}</span>
              <span v-else class="text-gray-500">{{ $t('manualBooking.searchTherapist') }}</span>
              <span class="flex items-center gap-2 shrink-0 ml-2">
                <span v-if="therapistsLoading" class="animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full" />
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </span>
            </button>
            <div
              v-if="showTherapistDropdown"
              class="absolute z-20 mt-1 left-0 right-0 bg-white border border-gray-300 rounded-md shadow-lg overflow-hidden"
            >
              <div class="p-2 border-b border-gray-200">
                <input
                  v-model="therapistSearch"
                  type="text"
                  class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                  :placeholder="$t('manualBooking.searchTherapist')"
                  @click.stop
                />
              </div>
              <div class="max-h-52 overflow-y-auto">
                <button
                  v-for="t in filteredTherapists"
                  :key="t.user_id"
                  type="button"
                  class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 border-b border-gray-100 last:border-0"
                  @click="selectTherapist(t)"
                >
                  {{ t.name || t.name_en || t.user_id }}<span v-if="t.phone"> — {{ t.phone }}</span>
                </button>
                <p v-if="filteredTherapists.length === 0" class="px-3 py-2 text-sm text-gray-500">{{ $t('manualBooking.noMatch') }}</p>
              </div>
            </div>
          </div>
          <button
            v-if="selectedTherapistId"
            type="button"
            class="rounded border border-gray-300 px-3 py-2 text-sm text-primary-600 hover:bg-gray-50 shrink-0"
            @click="clearTherapist"
          >
            {{ $t('manualBooking.clear') }}
          </button>
        </div>
        <p v-if="errors?.therapist" class="mt-1 text-sm text-red-600">{{ errors?.therapist }}</p>
      </div>

      <!-- Slot mode selector (stack on mobile, larger touch targets) -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
        <label class="inline-flex items-center gap-3 py-2.5 px-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 active:bg-gray-100 touch-manipulation">
          <input
            v-model="bookingSlotMode"
            type="radio"
            value="existing"
            class="w-4 h-4 text-primary-500 focus:ring-primary-500 shrink-0"
          >
          <span class="text-sm">{{ $t('manualBooking.slotModeExisting') }}</span>
        </label>
        <label class="inline-flex items-center gap-3 py-2.5 px-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 active:bg-gray-100 touch-manipulation">
          <input
            v-model="bookingSlotMode"
            type="radio"
            value="new"
            class="w-4 h-4 text-primary-500 focus:ring-primary-500 shrink-0"
          >
          <span class="text-sm">{{ $t('manualBooking.slotModeNew') }}</span>
        </label>
      </div>

      <!-- Date (existing slot) -->
      <div v-if="bookingSlotMode === 'existing'">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date') }}</label>
        <select v-model="selectedDate" class="w-full rounded border px-3 py-2" :class="errors?.date ? 'border-red-500' : 'border-gray-300'" @change="onDateChange">
          <option value="">— {{ $t('manualBooking.selectDate') }} —</option>
          <option v-for="d in availableDates" :key="d.date" :value="d.date">{{ d.label }}</option>
        </select>
        <p v-if="errors?.date" class="mt-1 text-sm text-red-600">{{ errors?.date }}</p>
        <span v-if="datesLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Date (new slot): any future date -->
      <div v-else>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date') }}</label>
        <input
          v-model="newSlotDate"
          type="date"
          class="w-full rounded border px-3 py-2 border-gray-300"
          :min="newSlotDateMin"
          @change="onNewSlotDateChange"
        >
      </div>

      <!-- Slot (existing slot) -->
      <div v-if="bookingSlotMode === 'existing'">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.slot') }}</label>
        <select v-model="selectedSlotId" class="w-full rounded border px-3 py-2" :class="errors?.slot ? 'border-red-500' : 'border-gray-300'">
          <option value="">— {{ $t('manualBooking.selectSlot') }} —</option>
          <option v-for="s in slots" :key="s.slot_id" :value="s.slot_id">{{ s.formatted_time }}</option>
        </select>
        <p v-if="errors?.slot" class="mt-1 text-sm text-red-600">{{ errors?.slot }}</p>
        <span v-if="slotsLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Slot (new slot): fixed base hours 8:15, 9:15, etc. -->
      <div v-else>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.slot') }}</label>
        <select v-model="newSlotTime" class="w-full rounded border px-3 py-2" :class="errors?.slot ? 'border-red-500' : 'border-gray-300'">
          <option value="">— {{ $t('manualBooking.selectSlot') }} —</option>
          <option v-for="t in baseTimeOptions" :key="t" :value="t">{{ formatTimeAmPm(t) }}</option>
        </select>
        <p v-if="errors?.slot" class="mt-1 text-sm text-red-600">{{ errors?.slot }}</p>
      </div>

      <!-- Country (pricing) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.country') }}</label>
        <select v-model="selectedCountryCode" class="w-full rounded border px-3 py-2" :class="errors?.country ? 'border-red-500' : 'border-gray-300'">
          <option value="">— {{ $t('manualBooking.selectCountry') }} —</option>
          <option v-for="c in therapistCountries" :key="c.code" :value="c.code">
            {{ c.name }} — {{ c.price }} {{ c.currency_symbol }}
          </option>
        </select>
        <p v-if="errors?.country" class="mt-1 text-sm text-red-600">{{ errors?.country }}</p>
        <span v-if="countriesLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Amount override (optional) - hidden for now -->
      <div v-if="false">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.amount') }}</label>
        <input v-model="amountOverride" type="text" class="w-full rounded border border-gray-300 px-3 py-2" />
        <p v-if="errors?.amount" class="mt-1 text-sm text-red-600">{{ errors?.amount }}</p>
      </div>

      <!-- Payment method -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.paymentMethod') }}</label>
        <select v-model="paymentMethod" class="w-full rounded border border-gray-300 px-3 py-2">
          <option value="">—</option>
          <option value="InstaPay">{{ $t('manualBooking.paymentInstaPay') }}</option>
          <option value="Wallet">{{ $t('manualBooking.paymentWallet') }}</option>
          <option value="Bank transfer">{{ $t('manualBooking.paymentBank') }}</option>
        </select>
      </div>

      <div class="pt-2">
        <button
          type="submit"
          class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50"
          :disabled="submitLoading"
        >
          <span v-if="submitLoading" class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2 align-middle" />
          {{ $t('manualBooking.createBooking') }}
        </button>
      </div>
    </form>

    <!-- Manage manual bookings -->
    <div v-else-if="activeTab === 'manage'" class="space-y-4">
      <div class="manual-booking-table-wrapper overflow-x-auto border rounded bg-white -mx-4 sm:mx-0">
        <div v-if="manageBookingsLoading" class="p-8 text-center text-gray-500">
          <span class="animate-spin inline-block h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
          <p class="mt-2">{{ $t('common.loading') }}</p>
        </div>
        <table v-else class="manual-booking-table min-w-full divide-y divide-gray-200 w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableOrderId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableDateTime') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistName') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistPhone') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePatientName') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePatientWhatsapp') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionPrice') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[260px] max-w-[260px]">{{ $t('manualBooking.tableMeetingLink') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePaymentMethod') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="row in manageBookings" :key="row.session_id" class="hover:bg-gray-50">
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.order_id }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(String(row.order_id))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.session_id }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(String(row.session_id))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ formatDateTime(row.date_time) }}</span>
                  <button v-if="row.date_time" type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(formatDateTime(row.date_time))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.therapist_name }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.therapist_name)">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.therapist_phone || '—' }}</span>
                  <button
                    v-if="row.therapist_phone"
                    type="button"
                    class="p-0.5 rounded hover:bg-gray-200"
                    title="Copy"
                    @click="copyCell(row.therapist_phone)"
                  >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.patient_name || '—' }}</span>
                  <button
                    v-if="row.patient_name"
                    type="button"
                    class="p-0.5 rounded hover:bg-gray-200"
                    title="Copy"
                    @click="copyCell(row.patient_name)"
                  >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.patient_whatsapp || '—' }}</span>
                  <button
                    v-if="row.patient_whatsapp"
                    type="button"
                    class="p-0.5 rounded hover:bg-gray-200"
                    title="Copy"
                    @click="copyCell(row.patient_whatsapp)"
                  >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ formatPrice(row.session_price) }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(formatPrice(row.session_price))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm overflow-hidden" style="max-width: 260px;">
                <button
                  v-if="row.meeting_link"
                  type="button"
                  class="px-2 py-1 rounded border border-gray-300 text-xs hover:bg-gray-100"
                  @click="copyCell(row.meeting_link)"
                >
                  نسخ رابط الجلسة
                </button>
                <span v-else>—</span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.payment_method }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.payment_method)">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <button
                  type="button"
                  class="px-2 py-1 rounded border border-primary-500 text-primary-600 text-xs hover:bg-primary-50"
                  @click="goToChangeBooking(row)"
                >
                  {{ $t('manualBooking.changeBooking') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="!manageBookingsLoading && manageBookings.length === 0" class="p-6 text-center text-gray-500">
          {{ $t('manualBooking.noBookings') }}
        </p>
        <!-- Pagination -->
        <div v-if="manageBookingsTotal > manageBookingsPerPage" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
          <span class="text-sm text-gray-600">
            {{ $t('manualBooking.showing') }} {{ (manageBookingsPage - 1) * manageBookingsPerPage + 1 }}-{{ Math.min(manageBookingsPage * manageBookingsPerPage, manageBookingsTotal) }} {{ $t('manualBooking.of') }} {{ manageBookingsTotal }}
          </span>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="manageBookingsPage <= 1"
              @click="loadManageBookings(manageBookingsPage - 1)"
            >
              {{ $t('common.previous') }}
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="manageBookingsPage >= manageBookingsTotalPages"
              @click="loadManageBookings(manageBookingsPage + 1)"
            >
              {{ $t('common.next') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Open slots (Jalsah AI booked slots by date) -->
    <div v-else-if="activeTab === 'openSlots'" class="space-y-4 mx-auto">
      <div class="flex flex-wrap items-end gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date') }}</label>
          <input
            v-model="openSlotsDate"
            type="date"
            class="rounded border border-gray-300 px-3 py-2"
            @keydown.enter.prevent="loadOpenSlots(1)"
          />
        </div>
        <button
          type="button"
          class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="openSlotsLoading || !openSlotsDate"
          @click="loadOpenSlots(1)"
        >
          <span v-if="openSlotsLoading" class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2 align-middle" />
          {{ $t('manualBooking.search') }}
        </button>
      </div>
      <div class="manual-booking-table-wrapper overflow-x-auto border rounded bg-white -mx-4 sm:mx-0">
        <div v-if="openSlotsLoading" class="p-8 text-center text-gray-500">
          <span class="animate-spin inline-block h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
          <p class="mt-2">{{ $t('common.loading') }}</p>
        </div>
        <template v-else>
          <table class="manual-booking-table min-w-full divide-y divide-gray-200 w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableOrderId') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionId') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableDateTime') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistName') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistPhone') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePatientName') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePatientWhatsapp') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionPrice') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTotalOrdersPatient') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[260px] max-w-[260px]">{{ $t('manualBooking.tableMeetingLink') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePaymentMethod') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="row in openSlots" :key="row.session_id" class="hover:bg-gray-50">
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ row.order_id }}</span>
                    <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(String(row.order_id))">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ row.session_id }}</span>
                    <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(String(row.session_id))">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ formatDateTime(row.date_time) }}</span>
                    <button v-if="row.date_time" type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(formatDateTime(row.date_time))">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ row.therapist_name }}</span>
                    <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.therapist_name)">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ row.therapist_phone || '—' }}</span>
                    <button v-if="row.therapist_phone" type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.therapist_phone)">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ row.patient_name || '—' }}</span>
                    <button v-if="row.patient_name" type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.patient_name)">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ row.patient_whatsapp || '—' }}</span>
                    <button v-if="row.patient_whatsapp" type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.patient_whatsapp)">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ formatPrice(row.session_price) }}</span>
                    <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(formatPrice(row.session_price))">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span>{{ row.total_patient_orders != null ? row.total_patient_orders : '—' }}</span>
                </td>
                <td class="px-3 py-2 text-sm overflow-hidden" style="max-width: 260px;">
                  <button
                    v-if="row.meeting_link"
                    type="button"
                    class="px-2 py-1 rounded border border-gray-300 text-xs hover:bg-gray-100"
                    @click="copyCell(row.meeting_link)"
                  >
                    نسخ رابط الجلسة
                  </button>
                  <span v-else>—</span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <span class="inline-flex items-center gap-1">
                    <span>{{ row.payment_method || '—' }}</span>
                    <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.payment_method)">
                      <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                  </span>
                </td>
                <td class="px-3 py-2 text-sm">
                  <button
                    type="button"
                    class="px-2 py-1 rounded border border-primary-500 text-primary-600 text-xs hover:bg-primary-50"
                    @click="goToChangeBooking(row)"
                  >
                    {{ $t('manualBooking.changeBooking') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <p v-if="openSlots.length === 0 && openSlotsDate" class="p-6 text-center text-gray-500">
            {{ $t('manualBooking.noOpenSlots') }}
          </p>
          <p v-if="!openSlotsDate" class="p-6 text-center text-gray-500">
            {{ $t('manualBooking.selectDateToViewOpenSlots') }}
          </p>
          <div v-if="openSlotsTotal > openSlotsPerPage" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
            <span class="text-sm text-gray-600">
              {{ $t('manualBooking.showing') }} {{ (openSlotsPage - 1) * openSlotsPerPage + 1 }}-{{ Math.min(openSlotsPage * openSlotsPerPage, openSlotsTotal) }} {{ $t('manualBooking.of') }} {{ openSlotsTotal }}
            </span>
            <div class="flex gap-2">
              <button
                type="button"
                class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="openSlotsPage <= 1"
                @click="loadOpenSlots(openSlotsPage - 1)"
              >
                {{ $t('common.previous') }}
              </button>
              <button
                type="button"
                class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="openSlotsPage >= openSlotsTotalPages"
                @click="loadOpenSlots(openSlotsPage + 1)"
              >
                {{ $t('common.next') }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Search by phone -->
    <div v-else-if="activeTab === 'searchByPhone'" class="space-y-4 mx-auto">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.phoneNumber') }}</label>
        <!-- Search target selector (patient / therapist) -->
        <div class="mb-2 flex gap-6 text-sm">
          <label class="inline-flex items-center gap-2">
            <input
              v-model="searchByPhoneMode"
              type="radio"
              class="text-primary-500 focus:ring-primary-500"
              value="patient"
            >
            <span>{{ $t('manualBooking.searchTargetPatient') }}</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input
              v-model="searchByPhoneMode"
              type="radio"
              class="text-primary-500 focus:ring-primary-500"
              value="therapist"
            >
            <span>{{ $t('manualBooking.searchTargetTherapist') }}</span>
          </label>
        </div>
        <!-- Patient search by phone -->
        <div v-if="searchByPhoneMode === 'patient'" class="flex gap-2">
          <input
            v-model="searchByPhoneQuery"
            type="text"
            inputmode="tel"
            class="flex-1 rounded border border-gray-300 px-3 py-2"
            :placeholder="$t('manualBooking.phonePlaceholder')"
            @keydown.enter.prevent="runSearchByPhone"
          />
          <button
            type="button"
            class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="searchByPhoneLoading || !searchByPhoneQuery.trim()"
            @click="runSearchByPhone"
          >
            <span v-if="searchByPhoneLoading" class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2 align-middle" />
            {{ $t('manualBooking.search') }}
          </button>
        </div>
        <!-- Therapist search using dropdown (same UX as new booking therapist selector) -->
        <div v-else class="flex gap-2 items-stretch" ref="searchTherapistDropdownRef">
          <div class="relative flex-1">
            <button
              type="button"
              class="w-full rounded border px-3 py-2 text-left flex items-center justify-between min-h-[42px]"
              :class="searchByPhoneSelectedTherapistId ? 'border-gray-300' : 'border-gray-300'"
              @click="showSearchTherapistDropdown = !showSearchTherapistDropdown"
            >
              <span v-if="selectedSearchByPhoneTherapistDisplay" class="truncate">{{ selectedSearchByPhoneTherapistDisplay }}</span>
              <span v-else class="text-gray-500">{{ $t('manualBooking.searchTherapist') }}</span>
              <span class="flex items-center gap-2 shrink-0 ml-2">
                <span v-if="therapistsLoading" class="animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full" />
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </span>
            </button>
            <div
              v-if="showSearchTherapistDropdown"
              class="absolute z-20 mt-1 left-0 right-0 bg-white border border-gray-300 rounded-md shadow-lg overflow-hidden"
            >
              <div class="p-2 border-b border-gray-200">
                <input
                  v-model="searchByPhoneTherapistSearch"
                  type="text"
                  class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                  :placeholder="$t('manualBooking.searchTherapist')"
                  @click.stop
                />
              </div>
              <div class="max-h-52 overflow-y-auto">
                <button
                  v-for="t in filteredTherapistsForSearchByPhone"
                  :key="t.user_id"
                  type="button"
                  class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 border-b border-gray-100 last:border-0"
                  @click="selectSearchByPhoneTherapist(t)"
                >
                  {{ t.name || t.name_en || t.user_id }}<span v-if="t.phone"> — {{ t.phone }}</span>
                </button>
                <p v-if="filteredTherapistsForSearchByPhone.length === 0" class="px-3 py-2 text-sm text-gray-500">
                  {{ $t('manualBooking.noMatch') }}
                </p>
              </div>
            </div>
          </div>
          <button
            v-if="searchByPhoneSelectedTherapistId"
            type="button"
            class="rounded border border-gray-300 px-3 py-2 text-sm text-primary-600 hover:bg-gray-50 shrink-0"
            @click="clearSearchByPhoneTherapist"
          >
            {{ $t('manualBooking.clear') }}
          </button>
          <button
            type="button"
            class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="searchByPhoneLoading || !searchByPhoneSelectedTherapistId"
            @click="runSearchByPhone"
          >
            <span v-if="searchByPhoneLoading" class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2 align-middle" />
            {{ $t('manualBooking.search') }}
          </button>
        </div>
        <p v-if="searchByPhoneError" class="mt-1 text-sm text-red-600">{{ searchByPhoneError }}</p>
      </div>
      <div v-if="searchByPhoneResult !== null" class="manual-booking-table-wrapper overflow-x-auto border rounded bg-white -mx-4 sm:mx-0">
        <p v-if="searchByPhoneResult.role" class="px-4 py-2 bg-gray-50 text-sm text-gray-600 border-b">
          {{ searchByPhoneResult.role === 'therapist' ? $t('manualBooking.bookingsForTherapist') : $t('manualBooking.bookingsForPatient') }}
        </p>
        <!-- Patient name when search by phone matched a patient -->
        <p
          v-if="searchByPhoneResult.role === 'patient' && searchByPhoneResult.patient_name"
          class="px-4 py-2 text-sm font-medium text-gray-800 border-b bg-white"
        >
          {{ $t('manualBooking.tablePatientName') }}: {{ searchByPhoneResult.patient_name }}
        </p>
        <div
          v-if="searchByPhoneResult.role === 'therapist' && searchByPhoneResult.therapist_settings"
          class="px-4 py-3 bg-gray-50 border-b text-xs sm:text-sm text-gray-700 space-y-1"
        >
          <div class="flex justify-between gap-4">
            <span class="font-medium">{{ $t('manualBooking.blockIfBeforeNumber') }}</span>
            <span>{{ formatBlockIfBefore(searchByPhoneResult.therapist_settings) }}</span>
          </div>
          <div class="flex justify-between gap-4">
            <span class="font-medium">{{ $t('manualBooking.formDaysCount') }}</span>
            <span>{{ searchByPhoneResult.therapist_settings.form_days_count || '—' }}</span>
          </div>
        </div>
        <div v-if="searchByPhoneResult.bookings.length === 0" class="p-6 text-center text-gray-500">
          {{ $t('manualBooking.noBookingsFound') }}
        </div>
        <table v-else class="manual-booking-table min-w-full divide-y divide-gray-200 w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableOrderId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableDateTime') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableType') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ searchByPhoneResult.role === 'therapist' ? $t('manualBooking.tablePatientName') : $t('manualBooking.tableTherapistName') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistPhone') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePatientWhatsapp') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionPrice') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[260px] max-w-[260px]">{{ $t('manualBooking.tableMeetingLink') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tablePaymentMethod') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="row in searchByPhoneResult.bookings" :key="row.session_id" class="hover:bg-gray-50">
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.order_id }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(String(row.order_id))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.session_id }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(String(row.session_id))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ formatDateTime(row.date_time) }}</span>
                  <button v-if="row.date_time" type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(formatDateTime(row.date_time))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span :class="row.booking_type === 'manual' ? 'text-amber-600 font-medium' : 'text-gray-700'">
                  {{ row.booking_type === 'manual' ? $t('manualBooking.typeManual') : $t('manualBooking.typeAi') }}
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ searchByPhoneResult.role === 'therapist' ? row.patient_name : row.therapist_name }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(searchByPhoneResult.role === 'therapist' ? row.patient_name : row.therapist_name)">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.therapist_phone || '—' }}</span>
                  <button
                    v-if="row.therapist_phone"
                    type="button"
                    class="p-0.5 rounded hover:bg-gray-200"
                    title="Copy"
                    @click="copyCell(row.therapist_phone)"
                  >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.patient_whatsapp || '—' }}</span>
                  <button
                    v-if="row.patient_whatsapp"
                    type="button"
                    class="p-0.5 rounded hover:bg-gray-200"
                    title="Copy"
                    @click="copyCell(row.patient_whatsapp)"
                  >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ formatPrice(row.session_price) }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(formatPrice(row.session_price))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm overflow-hidden" style="max-width: 260px;">
                <button
                  v-if="row.meeting_link"
                  type="button"
                  class="px-2 py-1 rounded border border-gray-300 text-xs hover:bg-gray-100"
                  @click="copyCell(row.meeting_link)"
                >
                  نسخ رابط الجلسة
                </button>
                <span v-else>—</span>
              </td>
              <td class="px-3 py-2 text-sm">
                <span class="inline-flex items-center gap-1">
                  <span>{{ row.payment_method }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.payment_method)">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm">
                <button
                  type="button"
                  class="px-2 py-1 rounded border border-primary-500 text-primary-600 text-xs hover:bg-primary-50"
                  @click="goToChangeBooking(row)"
                >
                  {{ $t('manualBooking.changeBooking') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <!-- Search by phone pagination -->
        <div v-if="searchByPhoneTotal > searchByPhonePerPage" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
          <span class="text-sm text-gray-600">
            {{ $t('manualBooking.showing') }} {{ (searchByPhonePage - 1) * searchByPhonePerPage + 1 }}-{{ Math.min(searchByPhonePage * searchByPhonePerPage, searchByPhoneTotal) }} {{ $t('manualBooking.of') }} {{ searchByPhoneTotal }}
          </span>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="searchByPhonePage <= 1"
              @click="runSearchByPhone(searchByPhonePage - 1)"
            >
              {{ $t('common.previous') }}
            </button>
            <button
              type="button"
              class="px-3 py-1 rounded border border-gray-300 text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="searchByPhonePage >= searchByPhoneTotalPages"
              @click="runSearchByPhone(searchByPhonePage + 1)"
            >
              {{ $t('common.next') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Availability copy -->
    <div v-else-if="activeTab === 'availabilityCopy'" class="space-y-4">
      <!-- Therapist selector -->
      <div ref="availabilityTherapistDropdownRef">
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.therapist') }}</label>
        <div class="flex gap-2 items-stretch">
          <div class="relative flex-1">
            <button
              type="button"
              class="w-full rounded border px-3 py-2 text-left flex items-center justify-between min-h-[42px]"
              :class="'border-gray-300'"
              @click="showAvailabilityTherapistDropdown = !showAvailabilityTherapistDropdown"
            >
              <span v-if="availabilitySelectedTherapistDisplay" class="truncate">{{ availabilitySelectedTherapistDisplay }}</span>
              <span v-else class="text-gray-500">{{ $t('manualBooking.searchTherapist') }}</span>
              <span class="flex items-center gap-2 shrink-0 ml-2">
                <span v-if="therapistsLoading" class="animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full" />
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </span>
            </button>
            <div
              v-if="showAvailabilityTherapistDropdown"
              class="absolute z-20 mt-1 left-0 right-0 bg-white border border-gray-300 rounded-md shadow-lg overflow-hidden"
            >
              <div class="p-2 border-b border-gray-200">
                <input
                  v-model="availabilityTherapistSearch"
                  type="text"
                  class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                  :placeholder="$t('manualBooking.searchTherapist')"
                  @click.stop
                />
              </div>
              <div class="max-h-52 overflow-y-auto">
                <button
                  v-for="t in filteredTherapistsForAvailability"
                  :key="t.user_id"
                  type="button"
                  class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100 border-b border-gray-100 last:border-0"
                  @click="selectAvailabilityTherapist(t)"
                >
                  {{ t.name || t.name_en || t.user_id }}<span v-if="t.phone"> — {{ t.phone }}</span>
                </button>
                <p v-if="filteredTherapistsForAvailability.length === 0" class="px-3 py-2 text-sm text-gray-500">{{ $t('manualBooking.noMatch') }}</p>
              </div>
            </div>
          </div>
          <button
            v-if="availabilitySelectedTherapistId"
            type="button"
            class="rounded border border-gray-300 px-3 py-2 text-sm text-primary-600 hover:bg-gray-50 shrink-0"
            @click="clearAvailabilityTherapist"
          >
            {{ $t('manualBooking.clear') }}
          </button>
        </div>
      </div>

      <!-- Date -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date') }}</label>
        <select
          v-model="availabilitySelectedDate"
          class="w-full rounded border border-gray-300 px-3 py-2"
          :disabled="!availabilitySelectedTherapistId"
          @change="onAvailabilityDateChange"
        >
          <option value="">— {{ $t('manualBooking.selectDate') }} —</option>
          <option v-for="d in availabilityDates" :key="d.date" :value="d.date">{{ d.label }}</option>
        </select>
        <span v-if="availabilityDatesLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <div v-if="availabilityHeading" class="rounded border border-gray-200 bg-white p-4">
        <p class="text-sm font-medium text-gray-800 whitespace-pre-line">{{ availabilityHeading }}</p>
        <p v-if="availabilitySlotsLoading" class="mt-2 text-sm text-gray-500">{{ $t('common.loading') }}</p>
        <p v-else-if="availabilitySelectedDate && !availabilitySlotsText" class="mt-2 text-sm text-gray-500">{{ $t('manualBooking.noSlots') }}</p>
        <pre class="mt-3 text-sm text-gray-700 whitespace-pre-wrap font-jalsah1">{{ availabilitySlotsText || '—' }}</pre>
        <button
          type="button"
          class="mt-3 px-3 py-2 rounded border border-primary-500 bg-primary-500 text-white text-sm hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!availabilityCopyText"
          @click="copyCell(availabilityCopyText)"
        >
          {{ $t('manualBooking.copyAvailability') }}
        </button>
      </div>
    </div>

    <!-- Change appointment -->
    <div v-else-if="activeTab === 'change'" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.searchAppointment') }}</label>
        <div class="flex gap-2">
          <input
            v-model="changeSearchQuery"
            type="text"
            class="flex-1 rounded border border-gray-300 px-3 py-2"
            :placeholder="$t('manualBooking.searchPlaceholder')"
            @input="onChangeSearchInput"
          />
          <span v-if="changeSearchLoading" class="flex items-center animate-spin h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
        </div>
      </div>
      <div v-if="changeSearchResults.length > 0" class="border rounded bg-white divide-y max-h-48 overflow-y-auto">
        <button
          v-for="a in changeSearchResults"
          :key="a.booking_id"
          type="button"
          class="w-full px-3 py-2 text-left text-sm hover:bg-gray-100"
          @click="selectAppointment(a)"
        >
          #{{ a.booking_id }} — {{ a.patient_name }} / {{ a.therapist_name }} — {{ formatDateTime(a.date_time) }}
        </button>
      </div>

      <template v-if="selectedAppointment">
        <!-- Current appointment details (being edited) -->
        <div class="border rounded bg-gray-50 p-4 mt-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">{{ $t('manualBooking.currentAppointment') }}</h3>
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
            <div>
              <dt class="text-gray-500">{{ $t('manualBooking.tableSessionId') }}</dt>
              <dd class="font-medium">{{ selectedAppointment.booking_id }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">{{ $t('manualBooking.patient') }}</dt>
              <dd class="font-medium">{{ selectedAppointment.patient_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">{{ $t('manualBooking.therapist') }}</dt>
              <dd class="font-medium">{{ selectedAppointment.therapist_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">{{ $t('manualBooking.currentDate') }}</dt>
              <dd class="font-medium">{{ formatDateTime(selectedAppointment.date_time) }}</dd>
            </div>
          </dl>
        </div>
        <div class="border-t pt-4 mt-4">
          <p class="text-sm text-gray-600 mb-2">{{ $t('manualBooking.selectNewSlot') }}</p>
          <!-- Slot mode: existing slot vs new slot (stack on mobile, larger touch targets) -->
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6 mb-4">
            <label class="inline-flex items-center gap-3 py-2.5 px-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 active:bg-gray-100 touch-manipulation">
              <input
                v-model="changeSlotMode"
                type="radio"
                value="existing"
                class="w-4 h-4 text-primary-500 focus:ring-primary-500 shrink-0"
              >
              <span class="text-sm">{{ $t('manualBooking.slotModeExisting') }}</span>
            </label>
            <label class="inline-flex items-center gap-3 py-2.5 px-3 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-50 active:bg-gray-100 touch-manipulation">
              <input
                v-model="changeSlotMode"
                type="radio"
                value="new"
                class="w-4 h-4 text-primary-500 focus:ring-primary-500 shrink-0"
              >
              <span class="text-sm">{{ $t('manualBooking.slotModeNew') }}</span>
            </label>
          </div>
          <!-- Existing slot: date dropdown + slot dropdown -->
          <div v-if="changeSlotMode === 'existing'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date') }}</label>
              <select v-model="changeSelectedDate" class="w-full rounded border border-gray-300 px-3 py-2" @change="onChangeDateChange">
                <option value="">— {{ $t('manualBooking.selectDate') }} —</option>
                <option v-for="d in changeAvailableDates" :key="d.date" :value="d.date">{{ d.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.slot') }}</label>
              <select v-model="changeSelectedSlotId" class="w-full rounded border border-gray-300 px-3 py-2">
                <option value="">— {{ $t('manualBooking.selectSlot') }} —</option>
                <option v-for="s in changeSlots" :key="s.slot_id" :value="s.slot_id">{{ s.formatted_time }}</option>
              </select>
            </div>
          </div>
          <!-- New slot: date input + time dropdown (same logic as new booking) -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date') }}</label>
              <input
                v-model="changeNewSlotDate"
                type="date"
                class="w-full rounded border border-gray-300 px-3 py-2"
                :min="changeNewSlotDateMin"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.slot') }}</label>
              <select v-model="changeNewSlotTime" class="w-full rounded border border-gray-300 px-3 py-2">
                <option value="">— {{ $t('manualBooking.selectSlot') }} —</option>
                <option v-for="t in baseTimeOptions" :key="t" :value="t">{{ formatTimeAmPm(t) }}</option>
              </select>
            </div>
          </div>
          <div class="mt-4">
            <button
              type="button"
              class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50"
              :disabled="changeSubmitLoading || !changeSlotValid"
              @click="submitChangeAppointment"
            >
              <span v-if="changeSubmitLoading" class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full mr-2 align-middle" />
              {{ $t('manualBooking.confirmChange') }}
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import Swal from 'sweetalert2'
import manualBookingApi from '@/services/manualBooking'
import api from '@/services/api'

const toast = useToast()
const { t, locale } = useI18n()
const activeTab = ref('new')

// Validation errors (translated)
const errors = ref({
  phone: '',
  firstName: '',
  lastName: '',
  therapist: '',
  date: '',
  slot: '',
  country: '',
  amount: ''
})

function clearErrors() {
  errors.value = {
    phone: '',
    firstName: '',
    lastName: '',
    therapist: '',
    date: '',
    slot: '',
    country: '',
    amount: ''
  }
}

function validateNewBooking() {
  clearErrors()
  let valid = true
  if (!patientId.value) {
    errors.value.phone = t('manualBooking.validation.patientRequired')
    valid = false
  }
  if (!patientFirstName.value.trim()) {
    errors.value.firstName = t('manualBooking.validation.firstNameRequired')
    valid = false
  }
  if (!selectedTherapistId.value) {
    errors.value.therapist = t('manualBooking.validation.therapistRequired')
    valid = false
  }
  if (!selectedDate.value) {
    errors.value.date = t('manualBooking.validation.dateRequired')
    valid = false
  }
  const slotValid = bookingSlotMode.value === 'new' ? newSlotTime.value : selectedSlotId.value
  if (!slotValid) {
    errors.value.slot = t('manualBooking.validation.slotRequired')
    valid = false
  }
  if (!selectedCountryCode.value) {
    errors.value.country = t('manualBooking.validation.countryRequired')
    valid = false
  }
  if (amountOverride.value && amountOverride.value.trim() !== '') {
    const num = parseFloat(amountOverride.value)
    if (isNaN(num) || num < 0) {
      errors.value.amount = t('manualBooking.validation.amountInvalid')
      valid = false
    }
  }
  return valid
}

// —— New booking ——
const patientPhoneDigits = ref('')
const patientCountrySearch = ref('')
const showPatientCountryDropdown = ref(false)
const patientCountries = ref([])
const selectedPatientCountryCode = ref('EG')
const patientSearchResults = ref([])
const patientSearchLoading = ref(false)
const patientId = ref(null)
const patientFirstName = ref('')
const patientLastName = ref('')
const therapists = ref([])
const therapistsLoading = ref(false)
const therapistSearch = ref('')
const showTherapistDropdown = ref(false)
const therapistDropdownRef = ref(null)
const selectedTherapistId = ref('')
const availableDates = ref([])
const datesLoading = ref(false)
const selectedDate = ref('')
const slots = ref([])
const slotsLoading = ref(false)
const selectedSlotId = ref('')
const therapistCountries = ref([])
const countriesLoading = ref(false)
const selectedCountryCode = ref('')
const amountOverride = ref('')
const paymentMethod = ref('')
const submitLoading = ref(false)
const bookingSlotMode = ref('existing')
const newSlotDate = ref('')
const newSlotTime = ref('')

const selectedPatientCountry = computed(() => {
  return patientCountries.value.find(c => c.country_code === selectedPatientCountryCode.value) || patientCountries.value[0]
})
const filteredPatientCountries = computed(() => {
  const q = patientCountrySearch.value.toLowerCase()
  if (!q) return patientCountries.value.slice(0, 50)
  return patientCountries.value.filter(c =>
    (c.name_en && c.name_en.toLowerCase().includes(q)) ||
    (c.name_ar && c.name_ar.toLowerCase().includes(q)) ||
    (c.dial_code && c.dial_code.includes(q)) ||
    (c.country_code && c.country_code.toLowerCase().includes(q))
  ).slice(0, 50)
})

const filteredTherapists = computed(() => {
  const q = therapistSearch.value.trim().toLowerCase()
  if (!q) {
    return therapists.value
  }
  return therapists.value.filter(t => {
    const name = (t.name || t.name_en || '').toLowerCase()
    const phone = (t.phone || t.whatsapp || '').toString().toLowerCase()
    return name.includes(q) || phone.includes(q)
  })
})

const selectedTherapistDisplay = computed(() => {
  if (!selectedTherapistId.value) return ''
  const t = therapists.value.find(t => t.user_id === selectedTherapistId.value)
  if (!t) return ''
  const name = t.name || t.name_en || String(t.user_id)
  return t.phone ? `${name} — ${t.phone}` : name
})

function selectTherapist(t) {
  selectedTherapistId.value = t.user_id
  showTherapistDropdown.value = false
  therapistSearch.value = ''
  onTherapistChange()
}

function clearTherapist() {
  selectedTherapistId.value = ''
  showTherapistDropdown.value = false
  therapistSearch.value = ''
  selectedDate.value = ''
  selectedSlotId.value = ''
  newSlotDate.value = ''
  newSlotTime.value = ''
  selectedCountryCode.value = ''
  availableDates.value = []
  slots.value = []
  therapistCountries.value = []
}

// Same phone validation as registration (auth.register.phoneValidation)
function validatePhoneNumber(phoneNumber, countryCode) {
  const country = patientCountries.value.find(c => c.country_code === countryCode)
  if (!country || !country.validation_pattern) {
    return { isValid: true, error: null }
  }
  let cleanPhoneNumber = (phoneNumber || '').replace(/[\s\-\(\)]/g, '')
  if (!/^\d+$/.test(cleanPhoneNumber)) {
    return { isValid: false, error: t('auth.register.phoneValidation.invalidCharacters') }
  }
  if (cleanPhoneNumber.startsWith('0')) {
    return { isValid: false, error: t('auth.register.phoneValidation.startsWithZero') }
  }
  const fullPhoneNumber = (country.dial_code || '') + cleanPhoneNumber
  const pattern = new RegExp(country.validation_pattern)
  if (!pattern.test(fullPhoneNumber)) {
    const isArabic = locale.value === 'ar'
    const customMessage = isArabic ? country.validation_message_ar : country.validation_message_en
    const error = (customMessage && customMessage.trim()) ? customMessage : t('auth.register.phoneValidation.invalidFormatForCountry')
    return { isValid: false, error }
  }
  return { isValid: true, error: null }
}

function onPatientPhoneInput(e) {
  const v = (e.target?.value || '').replace(/\D/g, '')
  patientPhoneDigits.value = v
  patientSearchResults.value = []
  if (errors.value.phone) errors.value.phone = ''
}

function onPatientPhoneBlur() {
  if (!patientPhoneDigits.value || !patientPhoneDigits.value.trim()) {
    if (errors.value.phone) errors.value.phone = ''
    return
  }
  const validation = validatePhoneNumber(patientPhoneDigits.value, selectedPatientCountryCode.value)
  if (!validation.isValid) {
    errors.value.phone = validation.error
  } else if (errors.value.phone) {
    errors.value.phone = ''
  }
}

async function runPatientSearch() {
  const digits = patientPhoneDigits.value.trim()
  if (!digits) {
    errors.value.phone = t('manualBooking.validation.patientRequired')
    return
  }
  const validation = validatePhoneNumber(digits, selectedPatientCountryCode.value)
  if (!validation.isValid) {
    errors.value.phone = validation.error
    toast.error(validation.error)
    return
  }
  errors.value.phone = ''
  patientSearchLoading.value = true
  patientSearchResults.value = []
  try {
    const dial = selectedPatientCountry.value?.dial_code?.replace('+', '') || '20'
    const q = dial + digits
    const data = await manualBookingApi.searchPatient(q)
    patientSearchResults.value = Array.isArray(data) ? data : []
  } catch (err) {
    patientSearchResults.value = []
    toast.error(err.response?.data?.error || t('manualBooking.messages.searchFailed'))
  } finally {
    patientSearchLoading.value = false
  }
}
function selectPatient(p) {
  patientId.value = p.id
  const first = p.first_name || ''
  const last = p.last_name || ''
  const combined = `${first} ${last}`.trim()
  patientFirstName.value = combined || p.name || p.email || ''
  patientLastName.value = last || ''
  patientSearchResults.value = []
  if (p.is_new) {
    toast.success(t('manualBooking.successDialog.newPatient'))
  }
}
function selectPatientCountry(c) {
  selectedPatientCountryCode.value = c.country_code
}

function onTherapistChange() {
  selectedDate.value = ''
  selectedSlotId.value = ''
  newSlotDate.value = ''
  newSlotTime.value = ''
  selectedCountryCode.value = ''
  availableDates.value = []
  slots.value = []
  therapistCountries.value = []
  if (!selectedTherapistId.value) return
  datesLoading.value = true
  countriesLoading.value = true
  Promise.all([
    manualBookingApi.getAvailableDates(selectedTherapistId.value),
    manualBookingApi.getTherapistCountries(selectedTherapistId.value)
  ]).then(([dates, countries]) => {
    availableDates.value = Array.isArray(dates) ? dates : []
    therapistCountries.value = Array.isArray(countries) ? countries : []
    if (therapistCountries.value.length) selectedCountryCode.value = therapistCountries.value[0].code
  }).catch(() => {
    toast.error(t('manualBooking.messages.loadDatesFailed'))
  }).finally(() => {
    datesLoading.value = false
    countriesLoading.value = false
  })
}
function onDateChange() {
  selectedSlotId.value = ''
  slots.value = []
  if (!selectedDate.value || !selectedTherapistId.value) return
  slotsLoading.value = true
  manualBookingApi.getSlots(selectedTherapistId.value, selectedDate.value).then(data => {
    slots.value = Array.isArray(data) ? data : []
  }).catch(() => toast.error(t('manualBooking.messages.loadSlotsFailed'))).finally(() => { slotsLoading.value = false })
}

function onNewSlotDateChange() {
  selectedDate.value = newSlotDate.value || ''
  newSlotTime.value = ''
}

function escapeOverlapHtml(str) {
  if (str == null || str === '') return ''
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

/** @returns {Promise<boolean>} true if overlap Swal was shown */
async function showOverlapErrorIfPresent(err) {
  const slots = err?.response?.data?.overlapping_slots
  if (!Array.isArray(slots) || slots.length === 0) {
    return false
  }
  const lines = slots.map((s) => {
    const sid = Number(s.slot_id) || 0
    const oid = s.order_id
      ? `<span class="block mt-1"><strong>${t('manualBooking.tableOrderId')}:</strong> #${Number(s.order_id)}</span>`
      : ''
    const st = s.session_status
      ? `<span class="block mt-1 text-gray-600"><strong>${t('manualBooking.overlap.statusLabel')}:</strong> ${escapeOverlapHtml(String(s.session_status))}</span>`
      : ''
    return `<li class="mb-3 pb-3 border-b border-gray-200 last:border-0"><strong>${t('manualBooking.tableSessionId')}:</strong> #${sid}<br/><strong>${t('manualBooking.tableDateTime')}:</strong> ${escapeOverlapHtml(String(s.date))} · ${escapeOverlapHtml(String(s.starts))} – ${escapeOverlapHtml(String(s.ends))}${oid}${st}</li>`
  }).join('')
  await Swal.fire({
    icon: 'warning',
    title: t('manualBooking.overlap.title'),
    html: `<p class="text-sm mb-3">${t('manualBooking.overlap.intro')}</p><ul class="text-sm list-none p-0 m-0">${lines}</ul>`,
    confirmButtonText: t('manualBooking.overlap.ok'),
    width: '34rem',
    didOpen: (popup) => {
      if (locale.value === 'ar') {
        const el = popup.querySelector('.swal2-popup')
        if (el) {
          el.setAttribute('dir', 'rtl')
        }
      }
    }
  })
  return true
}

async function submitNewBooking() {
  if (!validateNewBooking()) {
    return
  }
  const fullName = patientFirstName.value.trim()
  let firstNameForPayload = ''
  let lastNameForPayload = ''
  if (fullName) {
    const parts = fullName.split(' ')
    firstNameForPayload = parts.shift() || ''
    lastNameForPayload = (parts.join(' ').trim()) || firstNameForPayload
  }
  const payload = {
    mode: 'new',
    patient_id: patientId.value,
    therapist_id: selectedTherapistId.value,
    country_code: selectedCountryCode.value,
    patient_first_name: firstNameForPayload,
    patient_last_name: lastNameForPayload,
    payment_method: paymentMethod.value || ''
  }
  if (bookingSlotMode.value === 'new') {
    payload.date = selectedDate.value
    payload.time = newSlotTime.value
  } else {
    payload.slot_id = selectedSlotId.value
  }
  if (amountOverride.value && amountOverride.value.trim() !== '') {
    const num = parseFloat(amountOverride.value)
    if (!isNaN(num) && num > 0) payload.amount = num
  }
  submitLoading.value = true
  clearErrors()
  try {
    const result = await manualBookingApi.submit(payload)
    const therapistName = therapists.value.find(th => String(th.user_id) === String(selectedTherapistId.value))?.name || therapists.value.find(th => String(th.user_id) === String(selectedTherapistId.value))?.name_en || '—'
    const slotLabel = bookingSlotMode.value === 'new' ? formatTimeAmPm(newSlotTime.value) : slots.value.find(s => String(s.slot_id) === String(selectedSlotId.value))?.formatted_time
    const dateTimeStr = selectedDate.value && slotLabel ? `${selectedDate.value} ${slotLabel}` : (selectedDate.value || '—')
    const bookingId = result?.order_id ?? '—'
    await Swal.fire({
      icon: 'success',
      title: t('manualBooking.messages.bookingSuccess'),
      html: `<div class="text-left"><p class="mb-2"><strong>${t('manualBooking.tableOrderId')}:</strong> #${bookingId}</p><p class="mb-2"><strong>${t('manualBooking.successDialog.dateTime')}:</strong> ${dateTimeStr}</p><p><strong>${t('manualBooking.tableTherapistName')}:</strong> ${therapistName}</p></div>`
    })
    patientId.value = null
    patientFirstName.value = ''
    patientLastName.value = ''
    patientPhoneDigits.value = ''
    selectedTherapistId.value = ''
    selectedDate.value = ''
    selectedSlotId.value = ''
    newSlotDate.value = ''
    newSlotTime.value = ''
    selectedCountryCode.value = ''
    amountOverride.value = ''
    paymentMethod.value = ''
    availableDates.value = []
    slots.value = []
    therapistCountries.value = []
  } catch (err) {
    const shown = await showOverlapErrorIfPresent(err)
    if (!shown) {
      toast.error(err.response?.data?.error || t('manualBooking.messages.bookingFailed'))
    }
  } finally {
    submitLoading.value = false
  }
}

// —— Manage bookings ——
const manageBookings = ref([])
const manageBookingsLoading = ref(false)
const manageBookingsPage = ref(1)
const manageBookingsTotal = ref(0)
const manageBookingsPerPage = 100
const manageBookingsTotalPages = computed(() => Math.ceil(manageBookingsTotal.value / manageBookingsPerPage) || 1)

// —— Open slots ——
const openSlotsDate = ref('')
const openSlots = ref([])
const openSlotsLoading = ref(false)
const openSlotsPage = ref(1)
const openSlotsTotal = ref(0)
const openSlotsPerPage = 100
const openSlotsTotalPages = computed(() => Math.ceil(openSlotsTotal.value / openSlotsPerPage) || 1)

// —— Search by phone ——
const searchByPhoneMode = ref('patient')
const searchByPhoneQuery = ref('')
const searchByPhoneLoading = ref(false)
const searchByPhoneResult = ref(null)
const searchByPhoneError = ref('')
const searchByPhoneSelectedTherapistId = ref('')
const searchByPhoneTherapistSearch = ref('')
const showSearchTherapistDropdown = ref(false)
const searchTherapistDropdownRef = ref(null)
const searchByPhonePage = ref(1)
const searchByPhoneTotal = ref(0)
const searchByPhonePerPage = 100
const searchByPhoneLastPhone = ref('')
const searchByPhoneTotalPages = computed(() => Math.ceil(searchByPhoneTotal.value / searchByPhonePerPage) || 1)

// —— Availability copy ——
const availabilityTherapistSearch = ref('')
const showAvailabilityTherapistDropdown = ref(false)
const availabilityTherapistDropdownRef = ref(null)
const availabilitySelectedTherapistId = ref('')
const availabilityDates = ref([])
const availabilitySelectedDate = ref('')
const availabilitySlots = ref([])
const availabilitySlotsLoading = ref(false)
const availabilityDatesLoading = ref(false)

const filteredTherapistsForAvailability = computed(() => {
  const q = availabilityTherapistSearch.value.trim().toLowerCase()
  if (!q) {
    return therapists.value
  }
  return therapists.value.filter(t => {
    const name = (t.name || t.name_en || '').toLowerCase()
    const phone = (t.phone || t.whatsapp || '').toString().toLowerCase()
    return name.includes(q) || phone.includes(q)
  })
})

const availabilitySelectedTherapistDisplay = computed(() => {
  if (!availabilitySelectedTherapistId.value) return ''
  const th = therapists.value.find(t => String(t.user_id) === String(availabilitySelectedTherapistId.value))
  if (!th) return ''
  const name = th.name || th.name_en || String(th.user_id)
  return th.phone ? `${name} — ${th.phone}` : name
})

const availabilityHeading = computed(() => {
  if (!availabilitySelectedTherapistId.value || !availabilitySelectedDate.value) return ''
  const th = therapists.value.find(t => String(t.user_id) === String(availabilitySelectedTherapistId.value))
  const therapistName = th?.name || th?.name_en || '—'
  const date = availabilitySelectedDate.value
  const dayName = formatArabicDayName(date)
  return `المواعيد المتاحه للمعالج ${therapistName} ليوم ${dayName} الموافق ${date}`
})

const availabilitySlotsText = computed(() => {
  if (!availabilitySlots.value.length) return ''
  return availabilitySlots.value.map(s => s.formatted_time || s.time || '').filter(Boolean).join('\n')
})

const availabilityCopyText = computed(() => {
  if (!availabilityHeading.value) return ''
  if (!availabilitySlotsText.value) return availabilityHeading.value
  return `${availabilityHeading.value}\n${availabilitySlotsText.value}`
})

// Base times for new slot mode: 00:00 (midnight) to 11:15 PM at 15-min intervals
const baseTimeOptions = (() => {
  const options = []
  for (let h = 0; h <= 23; h++) {
    for (let m = 0; m < 60; m += 15) {
      if (h === 23 && m > 15) break
      options.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:00`)
    }
  }
  return options
})()

// New slot mode: min = today, allow any future date
const newSlotDateMin = computed(() => {
  const today = new Date()
  return today.toISOString().slice(0, 10)
})

// Change tab: same min date for new slot
const changeNewSlotDateMin = computed(() => {
  const today = new Date()
  return today.toISOString().slice(0, 10)
})

// Change tab: valid if existing slot selected or (new slot with date + time)
const changeSlotValid = computed(() => {
  if (changeSlotMode.value === 'existing') {
    return !!changeSelectedSlotId.value
  }
  return !!(changeNewSlotDate.value && changeNewSlotTime.value)
})

const filteredTherapistsForSearchByPhone = computed(() => {
  const q = searchByPhoneTherapistSearch.value.trim().toLowerCase()
  if (!q) {
    return therapists.value
  }
  return therapists.value.filter(t => {
    const name = (t.name || t.name_en || '').toLowerCase()
    const phone = (t.phone || t.whatsapp || '').toString().toLowerCase()
    return name.includes(q) || phone.includes(q)
  })
})

const selectedSearchByPhoneTherapistDisplay = computed(() => {
  if (!searchByPhoneSelectedTherapistId.value) return ''
  const t = therapists.value.find(t => t.user_id === searchByPhoneSelectedTherapistId.value)
  if (!t) return ''
  const name = t.name || t.name_en || String(t.user_id)
  return t.phone ? `${name} — ${t.phone}` : name
})

function selectSearchByPhoneTherapist(t) {
  searchByPhoneSelectedTherapistId.value = t.user_id
  showSearchTherapistDropdown.value = false
  searchByPhoneTherapistSearch.value = ''
}

function clearSearchByPhoneTherapist() {
  searchByPhoneSelectedTherapistId.value = ''
  searchByPhoneTherapistSearch.value = ''
  showSearchTherapistDropdown.value = false
}

function selectAvailabilityTherapist(t) {
  availabilitySelectedTherapistId.value = t.user_id
  showAvailabilityTherapistDropdown.value = false
  availabilityTherapistSearch.value = ''
  onAvailabilityTherapistChange()
}

function clearAvailabilityTherapist() {
  availabilitySelectedTherapistId.value = ''
  showAvailabilityTherapistDropdown.value = false
  availabilityTherapistSearch.value = ''
  availabilitySelectedDate.value = ''
  availabilityDates.value = []
  availabilitySlots.value = []
}

// Use same API and params as therapist booking form (form_days_count, off_days, block_if_before, attendance_type)
function onAvailabilityTherapistChange() {
  availabilitySelectedDate.value = ''
  availabilitySlots.value = []
  availabilityDates.value = []
  if (!availabilitySelectedTherapistId.value) return
  availabilityDatesLoading.value = true
  api.get('/api/ai/therapist-available-dates', {
    params: {
      therapist_id: availabilitySelectedTherapistId.value,
      attendance_type: 'online'
    }
  }).then(response => {
    const list = response?.data?.data?.available_dates || response?.data?.available_dates || []
    availabilityDates.value = (Array.isArray(list) ? list : []).map((item) => {
      const dateStr = typeof item === 'object' && item?.date ? item.date : item
      const d = dateStr ? new Date(dateStr + 'T00:00:00') : null
      const label = !d || isNaN(d.getTime())
        ? dateStr
        : d.toLocaleDateString(locale.value === 'ar' ? 'ar-EG' : 'en-US', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
      return { date: dateStr, label }
    })
  }).catch(() => {
    toast.error(t('manualBooking.messages.loadDatesFailed'))
  }).finally(() => {
    availabilityDatesLoading.value = false
  })
}

// Use same API and params as therapist booking form (period 45, online only, block_if_before, etc.)
function onAvailabilityDateChange() {
  availabilitySlots.value = []
  if (!availabilitySelectedDate.value || !availabilitySelectedTherapistId.value) return
  availabilitySlotsLoading.value = true
  api.get('/api/ai/therapist-availability', {
    params: {
      therapist_id: availabilitySelectedTherapistId.value,
      date: availabilitySelectedDate.value,
      attendance_type: 'online'
    }
  }).then(response => {
    const list = response?.data?.data?.available_slots || response?.data?.available_slots || []
    availabilitySlots.value = Array.isArray(list) ? list : []
  }).catch(() => {
    toast.error(t('manualBooking.messages.loadSlotsFailed'))
  }).finally(() => {
    availabilitySlotsLoading.value = false
  })
}

function formatArabicDayName(dateStr) {
  try {
    const d = new Date(`${dateStr}T00:00:00`)
    return new Intl.DateTimeFormat('ar-EG', { weekday: 'long' }).format(d)
  } catch (_) {
    return dateStr
  }
}

async function runSearchByPhone(page) {
  // If page is passed (pagination), use last search phone; otherwise resolve from form
  const isPagination = typeof page === 'number'
  let phone = isPagination ? searchByPhoneLastPhone.value : ''

  if (!isPagination) {
    searchByPhoneError.value = ''
    searchByPhoneResult.value = null

    if (searchByPhoneMode.value === 'patient') {
      phone = searchByPhoneQuery.value.trim()
      if (!phone) {
        searchByPhoneError.value = t('manualBooking.validation.phoneMinLength')
        return
      }
    } else {
      if (!searchByPhoneSelectedTherapistId.value) {
        searchByPhoneError.value = t('manualBooking.validation.therapistRequired')
        toast.error(t('manualBooking.validation.therapistRequired'))
        return
      }
      const therapist = therapists.value.find(th => String(th.user_id) === String(searchByPhoneSelectedTherapistId.value))
      const therapistPhone = (therapist?.phone || therapist?.whatsapp || '').toString().trim()
      if (!therapistPhone) {
        searchByPhoneError.value = t('manualBooking.messages.searchFailed')
        toast.error(t('manualBooking.messages.searchFailed'))
        return
      }
      phone = therapistPhone
    }
    searchByPhoneLastPhone.value = phone
    page = 1
  } else if (!searchByPhoneLastPhone.value) {
    return
  }

  searchByPhoneLoading.value = true
  try {
    const data = await manualBookingApi.getBookingsByPhone(phone, page, searchByPhonePerPage)
    const total = Number(data?.total) ?? (Array.isArray(data?.bookings) ? data.bookings.length : 0)
    searchByPhoneTotal.value = total
    searchByPhonePage.value = page
    searchByPhoneResult.value = {
      role: data?.role ?? '',
      bookings: Array.isArray(data?.bookings) ? data.bookings : [],
      total,
      therapist_settings: data?.therapist_settings || null,
      patient_name: data?.patient_name || ''
    }
  } catch (err) {
    if (!isPagination) {
      searchByPhoneError.value = err.response?.data?.error || t('manualBooking.messages.searchFailed')
      searchByPhoneResult.value = { role: '', bookings: [], total: 0 }
    } else {
      toast.error(err.response?.data?.error || t('manualBooking.messages.searchFailed'))
    }
  } finally {
    searchByPhoneLoading.value = false
  }
}

async function loadOpenSlots(page = 1) {
  if (!openSlotsDate.value) {
    toast.error(t('manualBooking.validation.dateRequired'))
    return
  }
  openSlotsLoading.value = true
  try {
    const data = await manualBookingApi.getOpenSlots(openSlotsDate.value, page, openSlotsPerPage)
    if (data && typeof data === 'object' && 'rows' in data) {
      openSlots.value = Array.isArray(data.rows) ? data.rows : []
      openSlotsTotal.value = Number(data.total) || 0
      openSlotsPage.value = page
    } else {
      openSlots.value = Array.isArray(data) ? data : []
      openSlotsTotal.value = openSlots.value.length
      openSlotsPage.value = 1
    }
  } catch (err) {
    openSlots.value = []
    openSlotsTotal.value = 0
    toast.error(err.response?.data?.error || t('manualBooking.messages.searchFailed'))
  } finally {
    openSlotsLoading.value = false
  }
}

async function loadManageBookings(page = 1) {
  manageBookingsLoading.value = true
  try {
    const data = await manualBookingApi.listBookings(page, manageBookingsPerPage)
    if (data && typeof data === 'object' && 'rows' in data) {
      manageBookings.value = Array.isArray(data.rows) ? data.rows : []
      manageBookingsTotal.value = Number(data.total) || 0
      manageBookingsPage.value = page
    } else {
      manageBookings.value = Array.isArray(data) ? data : []
      manageBookingsTotal.value = manageBookings.value.length
      manageBookingsPage.value = 1
    }
  } catch (err) {
    manageBookings.value = []
    manageBookingsTotal.value = 0
    toast.error(t('manualBooking.messages.searchFailed'))
  } finally {
    manageBookingsLoading.value = false
  }
}

function copyCell(text) {
  if (text == null || text === '') return
  const s = String(text)
  navigator.clipboard.writeText(s).then(() => {
    toast.success(t('manualBooking.copied') || 'Copied')
  }).catch(() => {
    toast.error(t('manualBooking.copyFailed') || 'Copy failed')
  })
}

function formatBlockIfBefore(settings) {
  if (!settings) return '—'
  const num = settings.block_if_before_number
  const unit = (settings.block_if_before_unit || '').toLowerCase()
  if (!num && !unit) return '—'
  const label = unit === 'day' ? t('manualBooking.unitDay') : (unit === 'hour' ? t('manualBooking.unitHour') : unit)
  return [num, label].filter(Boolean).join(' ')
}

function formatTimeAmPm(time) {
  if (!time) return ''
  const [h, m] = time.split(':')
  if (h == null || m == null) return time
  const d = new Date()
  d.setHours(Number(h), Number(m), 0, 0)
  return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

function formatPrice(price) {
  if (price == null || typeof price !== 'number') return '—'
  return new Intl.NumberFormat(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(price)
}

function goToChangeBooking(row) {
  selectedAppointment.value = {
    booking_id: row.session_id,
    patient_id: row.patient_id,
    therapist_id: row.therapist_id,
    patient_name: row.patient_name || '—',
    therapist_name: row.therapist_name,
    date_time: row.date_time
  }
  changeSearchQuery.value = String(row.session_id)
  changeSearchResults.value = []
  changeSelectedDate.value = ''
  changeSelectedSlotId.value = ''
  changeSlots.value = []
  if (row.therapist_id) {
    manualBookingApi.getAvailableDates(row.therapist_id).then(data => {
      changeAvailableDates.value = Array.isArray(data) ? data : []
    })
  } else {
    changeAvailableDates.value = []
  }
  activeTab.value = 'change'
}

// —— Change appointment ——
const changeSearchQuery = ref('')
const changeSearchResults = ref([])
const changeSearchLoading = ref(false)
const changeSearchDebounce = ref(null)
const selectedAppointment = ref(null)
const changeSlotMode = ref('existing')
const changeAvailableDates = ref([])
const changeSelectedDate = ref('')
const changeSlots = ref([])
const changeSelectedSlotId = ref('')
const changeNewSlotDate = ref('')
const changeNewSlotTime = ref('')
const changeSubmitLoading = ref(false)

function onChangeSearchInput() {
  if (changeSearchDebounce.value) clearTimeout(changeSearchDebounce.value)
  if (!changeSearchQuery.value.trim()) {
    changeSearchResults.value = []
    return
  }
  changeSearchLoading.value = true
  changeSearchDebounce.value = setTimeout(() => {
    manualBookingApi.searchAppointments(changeSearchQuery.value.trim()).then(data => {
      changeSearchResults.value = Array.isArray(data) ? data : []
    }).catch(() => {
      changeSearchResults.value = []
      toast.error(t('manualBooking.messages.searchFailed'))
    }).finally(() => { changeSearchLoading.value = false })
  }, 400)
}
function selectAppointment(a) {
  selectedAppointment.value = a
  changeSlotMode.value = 'existing'
  changeSelectedDate.value = ''
  changeSelectedSlotId.value = ''
  changeNewSlotDate.value = ''
  changeNewSlotTime.value = ''
  changeSlots.value = []
  if (!a.therapist_id) return
  manualBookingApi.getAvailableDates(a.therapist_id).then(data => {
    changeAvailableDates.value = Array.isArray(data) ? data : []
  })
}
function onChangeDateChange() {
  changeSelectedSlotId.value = ''
  changeSlots.value = []
  if (!changeSelectedDate.value || !selectedAppointment.value?.therapist_id) return
  manualBookingApi.getSlots(selectedAppointment.value.therapist_id, changeSelectedDate.value).then(data => {
    changeSlots.value = Array.isArray(data) ? data : []
  })
}
function formatDateTime(s) {
  if (!s) return '—'
  const d = new Date(s)
  return d.toLocaleString()
}

function resetChangeAppointmentState() {
  if (changeSearchDebounce.value) {
    clearTimeout(changeSearchDebounce.value)
    changeSearchDebounce.value = null
  }
  selectedAppointment.value = null
  changeSlotMode.value = 'existing'
  changeSearchQuery.value = ''
  changeSearchResults.value = []
  changeSearchLoading.value = false
  changeAvailableDates.value = []
  changeSelectedDate.value = ''
  changeSlots.value = []
  changeSelectedSlotId.value = ''
  changeNewSlotDate.value = ''
  changeNewSlotTime.value = ''
}

async function submitChangeAppointment() {
  if (!selectedAppointment.value || !changeSlotValid.value) return
  const payload = {
    mode: 'change',
    existing_booking_id: selectedAppointment.value.booking_id
  }
  if (changeSlotMode.value === 'new') {
    payload.date = changeNewSlotDate.value
    payload.time = changeNewSlotTime.value
  } else {
    payload.slot_id = changeSelectedSlotId.value
  }
  changeSubmitLoading.value = true
  try {
    const result = await manualBookingApi.submit(payload)
    const therapistName = selectedAppointment.value.therapist_name || '—'
    let dateTimeStr = '—'
    let newSessionId = '—'
    if (changeSlotMode.value === 'new') {
      dateTimeStr = changeNewSlotDate.value && changeNewSlotTime.value ? `${changeNewSlotDate.value} ${formatTimeAmPm(changeNewSlotTime.value)}` : (changeNewSlotDate.value || '—')
      newSessionId = result?.slot_id ?? '—'
    } else {
      const newSlot = changeSlots.value.find(s => String(s.slot_id) === String(changeSelectedSlotId.value))
      dateTimeStr = changeSelectedDate.value && newSlot?.formatted_time ? `${changeSelectedDate.value} ${newSlot.formatted_time}` : (changeSelectedDate.value || '—')
      newSessionId = changeSelectedSlotId.value
    }
    await Swal.fire({
      icon: 'success',
      title: t('manualBooking.messages.changeSuccess'),
      html: `<div class="text-left"><p class="mb-2"><strong>${t('manualBooking.tableSessionId')}:</strong> #${newSessionId}</p><p class="mb-2"><strong>${t('manualBooking.successDialog.dateTime')}:</strong> ${dateTimeStr}</p><p><strong>${t('manualBooking.tableTherapistName')}:</strong> ${therapistName}</p></div>`
    })
    resetChangeAppointmentState()
  } catch (err) {
    const shown = await showOverlapErrorIfPresent(err)
    if (!shown) {
      toast.error(err.response?.data?.error || t('manualBooking.messages.changeFailed'))
    }
  } finally {
    changeSubmitLoading.value = false
  }
}

function handleClickOutsideTherapist(e) {
  if (showTherapistDropdown.value && therapistDropdownRef.value && !therapistDropdownRef.value.contains(e.target)) {
    showTherapistDropdown.value = false
  }
  if (showSearchTherapistDropdown.value && searchTherapistDropdownRef.value && !searchTherapistDropdownRef.value.contains(e.target)) {
    showSearchTherapistDropdown.value = false
  }
  if (showAvailabilityTherapistDropdown.value && availabilityTherapistDropdownRef.value && !availabilityTherapistDropdownRef.value.contains(e.target)) {
    showAvailabilityTherapistDropdown.value = false
  }
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutsideTherapist)
  therapistsLoading.value = true
  try {
    const data = await manualBookingApi.getTherapists()
    therapists.value = Array.isArray(data) ? data : []
  } catch (e) {
    toast.error(t('manualBooking.messages.loadTherapistsFailed'))
  } finally {
    therapistsLoading.value = false
  }
  try {
    const res = await fetch('/countries-codes-and-flags.json')
    const json = await res.json()
    if (Array.isArray(json)) {
      const eg = json.find(c => c.country_code === 'EG')
      const rest = json.filter(c => c.country_code !== 'EG')
      patientCountries.value = eg ? [eg, ...rest] : json
    }
  } catch (_) {
    patientCountries.value = [
      { country_code: 'EG', name_en: 'Egypt', dial_code: '+20', flag: '🇪🇬' },
      { country_code: 'SA', name_en: 'Saudi Arabia', dial_code: '+966', flag: '🇸🇦' }
    ]
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutsideTherapist)
})
</script>

<style scoped>
.font-jalsah1,
.font-jalsah1 button {
  font-family: 'jalsah1', 'Cairo', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

/* Tabs: horizontal scroll on mobile, subtle scrollbar */
.manual-booking-tabs-scroll {
  -webkit-overflow-scrolling: touch;
}
.manual-booking-tabs-scroll::-webkit-scrollbar {
  height: 4px;
}
.manual-booking-tabs-scroll::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}

/* Tables: responsive horizontal scroll on mobile */
.manual-booking-table-wrapper {
  -webkit-overflow-scrolling: touch;
}
.manual-booking-table-wrapper::-webkit-scrollbar {
  height: 6px;
}
.manual-booking-table-wrapper::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 4px;
}
/* Force table to have minimum width so it scrolls horizontally on small screens */
.manual-booking-table {
  min-width: 880px;
  table-layout: auto;
}
@media (max-width: 640px) {
  .manual-booking-table th,
  .manual-booking-table td {
    padding: 0.5rem 0.375rem;
    font-size: 0.8125rem;
  }
  .manual-booking-table th {
    white-space: nowrap;
  }
}
</style>
