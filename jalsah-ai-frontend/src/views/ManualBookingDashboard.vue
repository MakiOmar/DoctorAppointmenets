<template>
  <div :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'" class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold text-primary-500 mb-6">
      {{ $t('manualBooking.title') }}
    </h1>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
      <button
        type="button"
        class="px-4 py-2 font-medium"
        :class="activeTab === 'new' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500'"
        @click="activeTab = 'new'"
      >
        {{ $t('manualBooking.newBooking') }}
      </button>
      <button
        type="button"
        class="px-4 py-2 font-medium"
        :class="activeTab === 'change' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500'"
        @click="activeTab = 'change'"
      >
        {{ $t('manualBooking.changeAppointment') }}
      </button>
      <button
        type="button"
        class="px-4 py-2 font-medium"
        :class="activeTab === 'manage' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500'"
        @click="activeTab = 'manage'; loadManageBookings()"
      >
        {{ $t('manualBooking.manageBookings') }}
      </button>
      <button
        type="button"
        class="px-4 py-2 font-medium"
        :class="activeTab === 'searchByPhone' ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500'"
        @click="activeTab = 'searchByPhone'"
      >
        {{ $t('manualBooking.searchByPhone') }}
      </button>
    </div>

    <!-- New booking -->
    <form v-if="activeTab === 'new'" class="space-y-4" @submit.prevent="submitNewBooking">
      <!-- Patient: country + phone -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.enterPhone') }}</label>
        <div class="flex rounded-md shadow-sm">
          <div class="relative flex-1 flex">
            <button
              type="button"
              class="inline-flex items-center px-3 rounded-l-md border border-gray-300 bg-gray-50 text-sm"
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
              class="flex-1 rounded-r-md border border-gray-300 px-3 py-2"
              :class="{ 'border-red-500': errors?.phone }"
              :placeholder="$t('manualBooking.phoneDigits')"
              @input="onPatientPhoneInput"
              @blur="onPatientPhoneBlur"
            />
          </div>
          <div class="ml-2 flex items-center gap-2">
            <button
              type="button"
              class="px-3 py-2 rounded-md border border-primary-500 bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="patientSearchLoading || !patientPhoneDigits.trim()"
              @click="runPatientSearch"
            >
              {{ $t('manualBooking.searchPatient') || 'Search' }}
            </button>
            <span v-if="patientSearchLoading" class="animate-spin h-5 w-5 border-2 border-primary-500 border-t-transparent rounded-full" />
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
            {{ p.name || p.email }} {{ p.first_name || p.last_name ? `(${p.first_name} ${p.last_name})` : '' }}
          </button>
        </div>
      </div>

      <!-- First / Last name -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.firstName') }} *</label>
          <input v-model="patientFirstName" type="text" class="w-full rounded border px-3 py-2" :class="errors?.firstName ? 'border-red-500' : 'border-gray-300'" />
          <p v-if="errors?.firstName" class="mt-1 text-sm text-red-600">{{ errors?.firstName }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.lastName') }} *</label>
          <input v-model="patientLastName" type="text" class="w-full rounded border px-3 py-2" :class="errors?.lastName ? 'border-red-500' : 'border-gray-300'" />
          <p v-if="errors?.lastName" class="mt-1 text-sm text-red-600">{{ errors?.lastName }}</p>
        </div>
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

      <!-- Date -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.date') }}</label>
        <select v-model="selectedDate" class="w-full rounded border px-3 py-2" :class="errors?.date ? 'border-red-500' : 'border-gray-300'" @change="onDateChange">
          <option value="">— {{ $t('manualBooking.selectDate') }} —</option>
          <option v-for="d in availableDates" :key="d.date" :value="d.date">{{ d.label }}</option>
        </select>
        <p v-if="errors?.date" class="mt-1 text-sm text-red-600">{{ errors?.date }}</p>
        <span v-if="datesLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
      </div>

      <!-- Slot -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.slot') }}</label>
        <select v-model="selectedSlotId" class="w-full rounded border px-3 py-2" :class="errors?.slot ? 'border-red-500' : 'border-gray-300'">
          <option value="">— {{ $t('manualBooking.selectSlot') }} —</option>
          <option v-for="s in slots" :key="s.slot_id" :value="s.slot_id">{{ s.formatted_time }}</option>
        </select>
        <p v-if="errors?.slot" class="mt-1 text-sm text-red-600">{{ errors?.slot }}</p>
        <span v-if="slotsLoading" class="ml-2 animate-spin h-4 w-4 border-2 border-primary-500 border-t-transparent rounded-full inline-block" />
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
      <div class="overflow-x-auto border rounded bg-white">
        <div v-if="manageBookingsLoading" class="p-8 text-center text-gray-500">
          <span class="animate-spin inline-block h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full" />
          <p class="mt-2">{{ $t('common.loading') }}</p>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200 table-fixed">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableOrderId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistName') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionPrice') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[180px] max-w-[180px]">{{ $t('manualBooking.tableMeetingLink') }}</th>
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
                  <span>{{ row.therapist_name }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(row.therapist_name)">
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
              <td class="px-3 py-2 text-sm overflow-hidden" style="max-width: 180px;">
                <div class="flex items-center gap-1 min-w-0">
                  <span class="min-w-0 break-all">{{ row.meeting_link || '—' }}</span>
                  <button v-if="row.meeting_link" type="button" class="p-0.5 rounded hover:bg-gray-200 shrink-0" title="Copy" @click="copyCell(row.meeting_link)">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </div>
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
      </div>
    </div>

    <!-- Search by phone -->
    <div v-else-if="activeTab === 'searchByPhone'" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('manualBooking.phoneNumber') }}</label>
        <div class="flex gap-2">
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
        <p v-if="searchByPhoneError" class="mt-1 text-sm text-red-600">{{ searchByPhoneError }}</p>
      </div>
      <div v-if="searchByPhoneResult !== null" class="overflow-x-auto border rounded bg-white">
        <p v-if="searchByPhoneResult.role" class="px-4 py-2 bg-gray-50 text-sm text-gray-600 border-b">
          {{ searchByPhoneResult.role === 'therapist' ? $t('manualBooking.bookingsForTherapist') : $t('manualBooking.bookingsForPatient') }}
        </p>
        <div v-if="searchByPhoneResult.bookings.length === 0" class="p-6 text-center text-gray-500">
          {{ $t('manualBooking.noBookingsFound') }}
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200 table-fixed">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableOrderId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionId') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableType') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableTherapistName') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">{{ $t('manualBooking.tableSessionPrice') }}</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-600 w-[180px] max-w-[180px]">{{ $t('manualBooking.tableMeetingLink') }}</th>
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
                <span :class="row.booking_type === 'manual' ? 'text-amber-600 font-medium' : 'text-gray-700'">
                  {{ row.booking_type === 'manual' ? $t('manualBooking.typeManual') : $t('manualBooking.typeAi') }}
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
                  <span>{{ formatPrice(row.session_price) }}</span>
                  <button type="button" class="p-0.5 rounded hover:bg-gray-200" title="Copy" @click="copyCell(formatPrice(row.session_price))">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </span>
              </td>
              <td class="px-3 py-2 text-sm overflow-hidden" style="max-width: 180px;">
                <div class="flex items-center gap-1 min-w-0">
                  <span class="min-w-0 break-all">{{ row.meeting_link || '—' }}</span>
                  <button v-if="row.meeting_link" type="button" class="p-0.5 rounded hover:bg-gray-200 shrink-0" title="Copy" @click="copyCell(row.meeting_link)">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                  </button>
                </div>
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
      </div>
    </div>

    <!-- Change appointment -->
    <div v-else class="space-y-4">
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
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
          <div class="mt-4">
            <button
              type="button"
              class="px-4 py-2 bg-primary-500 text-white rounded hover:opacity-90 disabled:opacity-50"
              :disabled="changeSubmitLoading || !changeSelectedSlotId"
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
  if (!patientLastName.value.trim()) {
    errors.value.lastName = t('manualBooking.validation.lastNameRequired')
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
  if (!selectedSlotId.value) {
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
  patientFirstName.value = p.first_name || ''
  patientLastName.value = p.last_name || ''
  patientSearchResults.value = []
}
function selectPatientCountry(c) {
  selectedPatientCountryCode.value = c.country_code
}

function onTherapistChange() {
  selectedDate.value = ''
  selectedSlotId.value = ''
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

async function submitNewBooking() {
  if (!validateNewBooking()) {
    return
  }
  const payload = {
    mode: 'new',
    patient_id: patientId.value,
    therapist_id: selectedTherapistId.value,
    slot_id: selectedSlotId.value,
    country_code: selectedCountryCode.value,
    patient_first_name: patientFirstName.value.trim(),
    patient_last_name: patientLastName.value.trim(),
    payment_method: paymentMethod.value || ''
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
    const selectedSlot = slots.value.find(s => String(s.slot_id) === String(selectedSlotId.value))
    const dateTimeStr = selectedDate.value && selectedSlot?.formatted_time ? `${selectedDate.value} ${selectedSlot.formatted_time}` : (selectedDate.value || '—')
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
    selectedCountryCode.value = ''
    amountOverride.value = ''
    paymentMethod.value = ''
    availableDates.value = []
    slots.value = []
    therapistCountries.value = []
  } catch (err) {
    toast.error(err.response?.data?.error || t('manualBooking.messages.bookingFailed'))
  } finally {
    submitLoading.value = false
  }
}

// —— Manage bookings ——
const manageBookings = ref([])
const manageBookingsLoading = ref(false)

// —— Search by phone ——
const searchByPhoneQuery = ref('')
const searchByPhoneLoading = ref(false)
const searchByPhoneResult = ref(null)
const searchByPhoneError = ref('')

async function runSearchByPhone() {
  const phone = searchByPhoneQuery.value.trim()
  if (!phone) return
  searchByPhoneError.value = ''
  searchByPhoneLoading.value = true
  searchByPhoneResult.value = null
  try {
    const data = await manualBookingApi.getBookingsByPhone(phone)
    searchByPhoneResult.value = { role: data?.role ?? '', bookings: Array.isArray(data?.bookings) ? data.bookings : [] }
  } catch (err) {
    searchByPhoneError.value = err.response?.data?.error || t('manualBooking.messages.searchFailed')
    searchByPhoneResult.value = { role: '', bookings: [] }
  } finally {
    searchByPhoneLoading.value = false
  }
}

async function loadManageBookings() {
  manageBookingsLoading.value = true
  try {
    const data = await manualBookingApi.listBookings()
    manageBookings.value = Array.isArray(data) ? data : []
  } catch (err) {
    manageBookings.value = []
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
const changeAvailableDates = ref([])
const changeSelectedDate = ref('')
const changeSlots = ref([])
const changeSelectedSlotId = ref('')
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
  changeSelectedDate.value = ''
  changeSelectedSlotId.value = ''
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
  changeSearchQuery.value = ''
  changeSearchResults.value = []
  changeSearchLoading.value = false
  changeAvailableDates.value = []
  changeSelectedDate.value = ''
  changeSlots.value = []
  changeSelectedSlotId.value = ''
}

async function submitChangeAppointment() {
  if (!selectedAppointment.value || !changeSelectedSlotId.value) return
  changeSubmitLoading.value = true
  try {
    const result = await manualBookingApi.submit({
      mode: 'change',
      existing_booking_id: selectedAppointment.value.booking_id,
      slot_id: changeSelectedSlotId.value
    })
    const therapistName = selectedAppointment.value.therapist_name || '—'
    const newSlot = changeSlots.value.find(s => String(s.slot_id) === String(changeSelectedSlotId.value))
    const dateTimeStr = changeSelectedDate.value && newSlot?.formatted_time ? `${changeSelectedDate.value} ${newSlot.formatted_time}` : (changeSelectedDate.value || '—')
    const newSessionId = changeSelectedSlotId.value
    await Swal.fire({
      icon: 'success',
      title: t('manualBooking.messages.changeSuccess'),
      html: `<div class="text-left"><p class="mb-2"><strong>${t('manualBooking.tableSessionId')}:</strong> #${newSessionId}</p><p class="mb-2"><strong>${t('manualBooking.successDialog.dateTime')}:</strong> ${dateTimeStr}</p><p><strong>${t('manualBooking.tableTherapistName')}:</strong> ${therapistName}</p></div>`
    })
    resetChangeAppointmentState()
  } catch (err) {
    toast.error(err.response?.data?.error || t('manualBooking.messages.changeFailed'))
  } finally {
    changeSubmitLoading.value = false
  }
}

function handleClickOutsideTherapist(e) {
  if (showTherapistDropdown.value && therapistDropdownRef.value && !therapistDropdownRef.value.contains(e.target)) {
    showTherapistDropdown.value = false
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
