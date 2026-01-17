<template>
  <div>

    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
              <h1 class="text-3xl text-gray-900 mb-8">{{ $t('appointmentsPage.title') }}</h1>

      <!-- Session Instructions -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <h3 class="text-lg text-blue-900 mb-3">{{ $t('session.instructions') }}</h3>
        <ul class="space-y-2 text-sm text-blue-800">
          <li class="flex items-start space-x-2 rtl:space-x-reverse">
            <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $t('session.instruction1') }}</span>
          </li>
          <li class="flex items-start space-x-2 rtl:space-x-reverse">
            <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $t('session.instruction2') }}</span>
          </li>
          <li class="flex items-start space-x-2 rtl:space-x-reverse">
            <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $t('session.instruction3') }}</span>
          </li>
        </ul>
      </div>

      <!-- Filter Tabs -->
      <div class="mb-8">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                activeTab === tab.id
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                'whitespace-nowrap py-2 px-1 border-b-2 text-sm'
              ]"
            >
              {{ tab.name }}
              <span 
                v-if="tab.count !== undefined"
                :class="[
                  activeTab === tab.id ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-900',
                  'ml-2 py-0.5 px-2.5 rounded-full text-xs'
                ]"
              >
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">{{ $t('appointmentsPage.loading') }}</p>
      </div>

      <!-- Tab Content -->
      <div v-else-if="activeTab !== 'rochtah'">
        <!-- Appointments List -->
        <div v-if="filteredAppointments.length > 0" class="space-y-6">
        <div 
          v-for="appointment in filteredAppointments" 
          :key="appointment.id"
          class="card"
        >
          <div class="md:flex md:items-center md:justify-between">
            <!-- Appointment Info -->
            <div class="md:flex md:items-center md:space-x-6">
              <!-- Therapist Image -->
                              <img 
                  :src="appointment.therapist?.photo || '/default-therapist.svg'" 
                  :alt="appointment.therapist?.name"
                  class="w-16 h-16 rounded-full mb-4 md:mb-0"
                  :class="appointment.therapist?.photo ? 'object-cover' : 'object-contain bg-gray-100 p-1'"
                />

              <!-- Details -->
              <div class="flex-1">
                <h3 class="text-lg text-gray-900 mb-2">
                  {{ appointment.therapist?.name }}
                </h3>
                <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-600">
                  <div>
                    <span class="font-medium">{{ $t('appointmentsPage.date') }}:</span> {{ formatDate(appointment.date) }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointmentsPage.time') }}:</span> {{ formatTime(appointment.time) }} {{ $t('dateTime.egyptTime') }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointmentsPage.duration') }}:</span> {{ appointment.session_type }} {{ $t('appointmentsPage.minutes') }}
                  </div>
                  <div>
                    <span class="font-medium">{{ $t('appointmentsPage.bookingId') }}:</span> {{ appointment.id }}
                  </div>
                </div>
                
                <!-- Notes -->
                <div v-if="appointment.notes" class="mt-3">
                  <span class="font-medium text-gray-900">{{ $t('appointmentsPage.notes') }}:</span>
                  <p class="text-gray-600 text-sm mt-1">{{ appointment.notes }}</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-4 md:mt-0 md:ml-6 flex flex-col space-y-2">
              <!-- Join Session Button - Available for both upcoming and previous appointments -->
              <button 
                v-if="canJoinSession(appointment)"
                @click="joinSession(appointment.id)"
                :disabled="appointment.status !== 'completed' && !appointment.therapist_joined"
                :class="[
                  'text-sm px-4 py-2 rounded-lg transition-colors',
                  (appointment.status === 'completed' || appointment.therapist_joined)
                    ? 'btn-primary' 
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed border border-gray-300'
                ]"
              >
                <span v-if="appointment.status === 'completed' || appointment.therapist_joined">
                  {{ $t('appointmentsPage.joinSession') }}
                </span>
                <span v-else class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ $t('session.waitingForTherapist') }}
                </span>
              </button>

              <!-- Testing: Simulate Therapist Join (non-production only) -->
              <button
                v-if="showSimulate && !appointment.therapist_joined && canJoinSession(appointment)"
                @click="simulateTherapistJoin(appointment)"
                class="text-xs px-3 py-1 rounded-lg border border-dashed border-gray-400 text-gray-600 hover:bg-gray-50"
                title="Simulate therapist joined (testing)"
              >
                Simulate Join
              </button>

              <!-- For Upcoming Sessions -->
              <template v-if="activeTab === 'upcoming'">
                <!-- Reschedule Button -->
                <button 
                  v-if="canReschedule(appointment)"
                  @click="rescheduleAppointment(appointment.id)"
                  class="btn-outline text-sm"
                >
                  {{ $t('appointmentsPage.reschedule') }}
                </button>
              </template>

              <!-- For Past Sessions -->
              <template v-if="activeTab === 'past'">
                <!-- Book a new appointment with the same therapist -->
                <button 
                  @click="bookWithSameTherapist(appointment)"
                  class="btn-primary text-sm"
                >
                  {{ $t('appointmentsPage.bookWithSameTherapist') }}
                </button>
              </template>
            </div>
          </div>

          <!-- Session Link (if available) -->
          <div v-if="appointment.session_link" class="mt-4 p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-blue-900">{{ $t('appointmentsPage.sessionLinkAvailable') }}</p>
                <p class="text-xs text-blue-700">{{ $t('appointmentsPage.sessionLinkMessage') }}</p>
              </div>
              <a 
                :href="appointment.session_link" 
                target="_blank"
                class="btn-primary text-sm"
              >
                {{ $t('appointmentsPage.joinNow') }}
              </a>
            </div>
          </div>
        </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <h3 class="text-lg text-gray-900 mb-2">{{ $t('appointmentsPage.noAppointments') }}</h3>
          <p class="text-gray-600 mb-6">
            {{ activeTab === 'upcoming' ? $t('appointmentsPage.noUpcoming') : 
               $t('appointmentsPage.noPast') }}
          </p>
          <button 
            @click="$router.push('/therapists')"
            class="btn-primary"
          >
            {{ $t('appointmentsPage.bookSession') }}
          </button>
        </div>
      </div>

      <!-- Rochtah Tab Content -->
      <div v-else-if="activeTab === 'rochtah'" class="space-y-6">
        <PrescriptionCard 
          :prescription-requests="prescriptionRequests"
          :completed-prescriptions="completedPrescriptions"
          @book-appointment="showRochtahBookingModal"
          @view-appointment="viewRochtahAppointment"
          @join-meeting="joinRochtahMeeting"
          @view-prescription="viewPrescriptionDetails"
        />
        
        <!-- Empty State for Rochtah Tab -->
        <div v-if="prescriptionRequests.length === 0 && completedPrescriptions.length === 0" class="text-center py-12">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h3 class="text-lg text-gray-900 mb-2">{{ $t('prescription.noPrescriptions') || 'No Prescriptions' }}</h3>
          <p class="text-gray-600">{{ $t('prescription.noPrescriptionsMessage') || 'You don\'t have any prescription requests or completed prescriptions yet.' }}</p>
        </div>
      </div>
    </div>

    <!-- Session Modal -->
    <div v-if="showSessionModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-8 mx-auto w-11/12 max-w-6xl bg-white rounded-lg shadow-xl">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <h3 class="text-lg text-gray-900">{{ $t('session.meetingRoom') }}</h3>
          <div class="flex items-center space-x-2 rtl:space-x-reverse">
            <!-- Exit Session Button -->
            <button 
              v-if="jitsiLoaded"
              @click="exitSession"
              class="bg-red-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-red-700 transition-colors"
            >
              {{ $t('session.exitSession') }}
            </button>
          </div>
        </div>
        <!-- Modal Body -->
        <div class="w-full relative" style="height: 80vh;">
          <!-- Jitsi Meeting Container (always rendered) -->
          <div id="session-meeting" class="w-full h-full" :class="{ 'hidden': !jitsiLoaded }"></div>
          <!-- Loading State -->
          <div v-if="!jitsiLoaded" class="flex items-center justify-center h-full absolute inset-0">
            <div class="text-center">
              <svg class="animate-spin h-12 w-12 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <p class="text-gray-600">{{ $t('session.loadingMeeting') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div v-if="showCancelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <h3 class="text-lg text-gray-900 mb-4">{{ $t('appointmentsPage.cancelTitle') }}</h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ $t('appointmentsPage.cancelMessage') }}
          </p>
          <div class="flex justify-center space-x-4">
            <button 
              @click="confirmCancel"
              :disabled="cancelling"
              class="btn-primary"
            >
              <span v-if="cancelling" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('appointmentsPage.cancelling') }}
              </span>
              <span v-else>{{ $t('appointmentsPage.yesCancel') }}</span>
            </button>
            <button 
              @click="showCancelModal = false"
              class="btn-outline"
            >
              {{ $t('appointmentsPage.noKeep') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Rochtah Booking Modal -->
    <div v-if="showRochtahModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mb-4">
          <div class="flex justify-between items-center">
            <h3 class="text-lg text-gray-900">{{ $t('prescription.bookFreeAppointment') }}</h3>
            <button @click="closeRochtahModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <p class="text-sm text-gray-600 mt-2">(جميع المواعيد بتوقيت مصر)</p>
        </div>

        <!-- Loading State -->
        <div v-if="loadingSlots" class="text-center py-8">
          <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600">{{ $t('appointmentsPage.loading') }}</p>
        </div>

        <!-- Available Slots -->
        <div v-else-if="availableSlots.length > 0" class="max-h-96 overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div 
              v-for="(slot, index) in availableSlots" 
              :key="`${slot.date}-${slot.time}`"
              @click="selectSlot(slot)"
              :class="[
                'p-3 border rounded-lg cursor-pointer transition-colors',
                selectedSlot && selectedSlot.date === slot.date && selectedSlot.time === slot.time
                  ? 'border-primary-500 bg-primary-50'
                  : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50'
              ]"
              :style="{ order: index }"
            >
              <div class="font-medium text-gray-900">{{ formatDate(slot.date) }}</div>
              <div class="text-sm text-gray-600">{{ slot.formatted_time }}</div>
            </div>
          </div>
        </div>

        <!-- No Slots Available -->
        <div v-else class="text-center py-8">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <p class="text-gray-600">{{ $t('prescription.noAvailableSlots') }}</p>
        </div>

        <!-- Action Buttons -->
        <div v-if="availableSlots.length > 0" class="flex justify-end space-x-3 mt-6 pt-4 border-t">
          <button 
            @click="closeRochtahModal"
            class="btn-outline"
          >
            {{ $t('common.cancel') }}
          </button>
          <button 
            @click="bookRochtahAppointment"
            :disabled="!selectedSlot || bookingRochtah"
            class="btn-primary"
          >
            <span v-if="bookingRochtah" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t('appointmentsPage.booking') }}
            </span>
            <span v-else>{{ $t('prescription.bookAppointment') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Booking Confirmation Modal -->
    <div v-if="showBookingConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <h3 class="text-lg text-gray-900 mb-4">{{ $t('prescription.confirmBooking') }}</h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ $t('prescription.confirmBookingMessage') }}
          </p>
          <div class="flex justify-center space-x-4">
            <button 
              @click="confirmRochtahBooking"
              :disabled="bookingRochtah"
              class="btn-primary"
            >
              <span v-if="bookingRochtah" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('appointmentsPage.booking') }}
              </span>
              <span v-else>{{ $t('common.yes') }}</span>
            </button>
            <button 
              @click="showBookingConfirmModal = false"
              class="btn-outline"
            >
              {{ $t('common.no') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Rochtah Session Modal -->
    <div v-if="showRochtahSessionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl text-gray-900">{{ $t('prescription.rochtahSession') || 'Rochtah Session' }}</h3>
          <button 
            @click="closeRochtahSessionModal"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div v-if="rochtahMeetingDetails" class="space-y-4">
          <!-- Session Info -->
          <div class="bg-blue-50 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-700">{{ $t('appointmentsPage.date') }}:</span>
                <span class="text-gray-900">{{ formatDate(rochtahMeetingDetails.booking_date) }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">{{ $t('appointmentsPage.time') }}:</span>
                <span class="text-gray-900">{{ formatTime(rochtahMeetingDetails.booking_time) }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-700">{{ $t('prescription.roomName') || 'Room' }}:</span>
                <span class="text-gray-900">{{ rochtahMeetingDetails.room_name }}</span>
              </div>
            </div>
          </div>
          
          <!-- Meeting Room -->
          <div class="bg-gray-900 rounded-lg overflow-hidden" style="height: 600px;">
            <div id="rochtah-meeting" class="w-full h-full"></div>
          </div>
          
          <!-- Meeting Controls -->
          <div class="flex justify-center space-x-4">
            <!-- Start button is hidden as meeting auto-starts when modal opens -->
            <button 
              @click="closeRochtahSessionModal"
              class="btn-outline px-6 py-3"
            >
              {{ $t('common.close') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Prescription Details Modal -->
    <div v-if="showPrescriptionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <!-- Modal Header -->
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl text-gray-900">
              {{ $t('prescription.prescriptionDetails') }}
            </h3>
            <button 
              @click="closePrescriptionModal"
              class="text-gray-400 hover:text-gray-600 text-2xl"
            >
              ×
            </button>
          </div>

          <div v-if="selectedPrescription" class="space-y-4">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-blue-50 rounded-lg">
              <div>
                <span class="font-medium text-gray-700">{{ $t('prescription.prescribedBy') }}:</span>
                <div class="text-gray-900">{{ selectedPrescription.prescribed_by_name }}</div>
              </div>
              <div>
                <span class="font-medium text-gray-700">{{ $t('prescription.prescribedAt') }}:</span>
                <div class="text-gray-900">{{ formatDate(selectedPrescription.prescribed_at) }}</div>
              </div>
            </div>

            <!-- Prescription Text -->
            <div v-if="selectedPrescription.prescription_text" class="p-4 bg-gray-50 rounded-lg">
              <h4 class="font-medium text-gray-700 mb-2">{{ $t('prescription.prescriptionText') }}:</h4>
              <div class="text-gray-900 whitespace-pre-wrap">{{ selectedPrescription.prescription_text }}</div>
            </div>

            <!-- Initial Diagnosis & Symptoms -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-if="selectedPrescription.initial_diagnosis" class="p-4 bg-orange-50 rounded-lg">
                <h4 class="font-medium text-gray-700 mb-2">{{ $t('prescription.initialDiagnosis') }}:</h4>
                <div class="text-gray-900 whitespace-pre-wrap">{{ selectedPrescription.initial_diagnosis }}</div>
              </div>
              <div v-if="selectedPrescription.symptoms" class="p-4 bg-red-50 rounded-lg">
                <h4 class="font-medium text-gray-700 mb-2">{{ $t('prescription.symptoms') }}:</h4>
                <div class="text-gray-900 whitespace-pre-wrap">{{ selectedPrescription.symptoms }}</div>
              </div>
            </div>

            <!-- Reason for Referral -->
            <div v-if="selectedPrescription.reason_for_referral" class="p-4 bg-teal-50 rounded-lg">
              <h4 class="font-medium text-gray-700 mb-2">{{ $t('prescription.reasonForReferral') }}:</h4>
              <div class="text-gray-900 whitespace-pre-wrap">{{ selectedPrescription.reason_for_referral }}</div>
            </div>

            <!-- Attachments -->
            <div v-if="selectedPrescription.attachments && selectedPrescription.attachments.length > 0" class="p-4 bg-indigo-50 rounded-lg">
              <h4 class="font-medium text-gray-700 mb-3">{{ $t('prescription.attachments') || 'المرفقات' }}:</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div 
                  v-for="(attachment, idx) in selectedPrescription.attachments" 
                  :key="idx"
                  class="relative group"
                >
                  <a 
                    :href="attachment.url" 
                    target="_blank"
                    class="block border-2 border-gray-200 rounded-lg overflow-hidden hover:border-blue-500 transition-colors"
                  >
                    <div v-if="attachment.is_image" class="aspect-square bg-gray-100">
                      <img 
                        :src="attachment.thumbnail || attachment.url" 
                        :alt="attachment.name"
                        class="w-full h-full object-cover"
                      />
                    </div>
                    <div v-else class="aspect-square bg-gray-100 flex items-center justify-center p-4">
                      <div class="text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-xs text-gray-600 truncate px-2">{{ attachment.name }}</p>
                      </div>
                    </div>
                  </a>
                  <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-opacity flex items-center justify-center">
                    <a 
                      :href="attachment.url" 
                      target="_blank"
                      class="opacity-0 group-hover:opacity-100 bg-white rounded-full p-2 shadow-lg transition-opacity"
                      :title="$t('prescription.viewAttachment') || 'عرض المرفق'"
                    >
                      <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                      </svg>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex justify-end pt-6 border-t">
            <button 
              @click="closePrescriptionModal"
              class="btn-outline px-6 py-2"
            >
              {{ $t('common.close') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking Modal -->
    <div v-if="showBookingModal && selectedTherapist" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="closeBookingModal">
      <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
      <!-- Modal Header -->
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl text-gray-900">
          {{ $t('appointmentsPage.bookWithSameTherapist') }} - {{ selectedTherapist.name }}
        </h3>
        <button
          @click="closeBookingModal"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="space-y-6">
        <!-- Date Selection -->
        <div v-if="loadingDates" class="text-center py-4">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600 mx-auto"></div>
          <p class="text-sm text-gray-600 mt-2">{{ $t('therapistDetails.loadingDates') }}</p>
        </div>
        
        <div v-else-if="availableDates.length > 0" class="space-y-4">
          <!-- Date Carousel -->
          <div>
            <h4 class="text-lg text-gray-900 mb-3">{{ $t('therapistDetails.selectDate') }}</h4>
            <div class="flex overflow-x-auto gap-3 pb-2 scrollbar-hide">
              <button
                v-for="date in availableDates"
                :key="date.value"
                @click="selectDate(date)"
                class="flex-shrink-0 px-4 py-2 rounded-lg border text-sm transition-colors"
                :class="selectedDate?.value === date.value 
                  ? 'border-primary-600 bg-primary-50 text-primary-700' 
                  : 'border-gray-300 bg-white text-gray-700 hover:border-primary-400'"
              >
                <div class="text-center">
                  <div class="font-semibold">{{ date.date }}</div>
                </div>
              </button>
            </div>
          </div>

          <!-- Loading Time Slots -->
          <div v-if="selectedDate && bookingLoadingSlots" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
            <h5 class="font-medium text-gray-900 mb-3">{{ $t('therapistDetails.availableTimes') }}</h5>
            <div class="text-center py-4">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600 mx-auto"></div>
              <p class="text-sm text-gray-600 mt-2">{{ $t('therapistDetails.loadingTimes') }}</p>
            </div>
          </div>

          <!-- Time Slots Grid -->
          <div v-else-if="selectedDate && timeSlots.length > 0" class="bg-gray-50 rounded-lg border border-gray-200 p-4">
            <h5 class="font-medium text-gray-900 mb-3">{{ $t('therapistDetails.availableTimes') }}</h5>
            <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
              <div
                v-for="slot in timeSlots"
                :key="slot.slot_id"
                class="relative"
              >
                <button
                  v-if="!slot.inCart"
                  @click="addToCart(slot)"
                  :disabled="cartLoading[slot.slot_id]"
                  class="w-full px-3 py-2 text-sm rounded border transition-colors border-gray-300 bg-white text-gray-700 hover:border-primary-400 hover:bg-primary-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="cartLoading[slot.slot_id]" class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600 mr-2"></div>
                    {{ $t('common.loading') }}
                  </span>
                  <span v-else>{{ formatTimeSlot(slot.time) }}</span>
                </button>
                <div
                  v-else
                  class="w-full px-3 py-2 text-sm rounded border border-green-600 bg-green-50 text-green-700 flex items-center justify-between"
                >
                  <span>{{ formatTimeSlot(slot.time) }}</span>
                  <button
                    @click="removeFromCart(slot)"
                    :disabled="cartLoading[slot.slot_id]"
                    class="ml-2 text-red-600 hover:text-red-800 text-xs disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Remove from cart"
                  >
                    <span v-if="cartLoading[slot.slot_id]" class="flex items-center">
                      <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-red-600 mr-1"></div>
                      {{ $t('common.loading') }}
                    </span>
                    <span v-else>{{ $t('common.remove') }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- No Time Slots -->
          <div v-else-if="selectedDate && timeSlots.length === 0 && !bookingLoadingSlots" class="text-center py-4 text-gray-500">
            {{ $t('therapistDetails.noTimeSlots') }}
          </div>
        </div>

        <!-- No Available Dates -->
        <div v-else class="text-center py-4 text-gray-500">
          {{ $t('therapistDetails.noAvailableDates') }}
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="mt-6 flex justify-end">
        <button
          @click="closeBookingModal"
          class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors"
        >
          {{ $t('common.close') }}
        </button>
      </div>
      </div>
    </div>
  </div>
</template>

<script>
import { 
  ref, 
  computed, 
  onMounted, 
  onUnmounted, 
  nextTick, 
  watch 
} from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import api from '@/services/api'
import PrescriptionCard from '@/components/PrescriptionCard.vue'
import Swal from 'sweetalert2'
import { formatGregorianDate } from '@/utils/dateFormatter'
export default {
  name: 'Appointments',
  components: {
    PrescriptionCard
  },
  setup() {
    const router = useRouter()
    const toast = useToast()
    const { t: $t, locale } = useI18n()
    const authStore = useAuthStore()
    const cartStore = useCartStore()
    
    const loading = ref(true)
    const cancelling = ref(false)
    const showCancelModal = ref(false)
    const appointments = ref([])
    const activeTab = ref('upcoming')
    const appointmentToCancel = ref(null)
    
    // Rochtah booking related refs
    const prescriptionRequests = ref([])
    const completedPrescriptions = ref([])
    const showRochtahModal = ref(false)
    const showBookingConfirmModal = ref(false)
    const loadingSlots = ref(false)
    const bookingRochtah = ref(false)
    const availableSlots = ref([])
    const selectedSlot = ref(null)
    const currentRequestId = ref(null)
    
    // Booking modal state
    const showBookingModal = ref(false)
    const selectedTherapist = ref(null)
    const availableDates = ref([])
    const selectedDate = ref(null)
    const timeSlots = ref([])
    const loadingDates = ref(false)
    const bookingLoadingSlots = ref(false)
    const cartLoading = ref({})
    
    // Therapist join status polling
    const pollingInterval = ref(null)
    const isPolling = ref(false)
    
    // Rochtah session related refs
    const showRochtahSessionModal = ref(false)
    const rochtahMeetingDetails = ref(null)
    const rochtahMeetingAPI = ref(null)

    // Prescription viewing related refs
    const showPrescriptionModal = ref(false)
    const selectedPrescription = ref(null)
    
    // Popup management
    const openPopups = ref(new Map())
    const showSimulate = ref(!import.meta.env.PROD)
    const showSessionModal = ref(false)
    const sessionIframeSrc = ref('')
    const jitsiLoaded = ref(false)
    const sessionMeetAPI = ref(null)
    const currentSessionId = ref(null)

    const tabs = computed(() => {
      const tabList = [
        { 
          id: 'upcoming', 
          name: $t('appointmentsPage.tabs.upcoming'), 
          count: appointments.value.filter(a => a.status === 'confirmed' || a.status === 'pending' || a.status === 'open').length 
        },
        { 
          id: 'past', 
          name: $t('appointmentsPage.tabs.past'), 
          count: appointments.value.filter(a => a.status === 'completed').length 
        }
      ]
      
      // Add rochtah tab only if there are prescription requests or completed prescriptions
      if (prescriptionRequests.value.length > 0 || completedPrescriptions.value.length > 0) {
        tabList.push({
          id: 'rochtah',
          name: $t('prescription.rochtah') || 'روشتا',
          count: prescriptionRequests.value.length + completedPrescriptions.value.length
        })
      }
      
      return tabList
    })

    const filteredAppointments = computed(() => {
      // Don't filter if rochtah tab is active (rochtah content is handled separately)
      if (activeTab.value === 'rochtah') {
        return []
      }
      
      let filtered = []
      
      switch (activeTab.value) {
        case 'upcoming':
          filtered = appointments.value.filter(a => a.status === 'confirmed' || a.status === 'pending' || a.status === 'open')
          // Sort upcoming sessions from nearest to farthest
          return filtered.sort((a, b) => {
            const dateA = new Date(a.date_time || a.date)
            const dateB = new Date(b.date_time || b.date)
            return dateA - dateB
          })
        case 'past':
          filtered = appointments.value.filter(a => a.status === 'completed')
          // Sort past sessions from newest to oldest
          return filtered.sort((a, b) => {
            const dateA = new Date(a.date_time || a.date)
            const dateB = new Date(b.date_time || b.date)
            return dateB - dateA
          })
        default:
          return appointments.value
      }
    })

    const loadAppointments = async () => {
      loading.value = true
      try {
        const response = await api.get('/api/ai/appointments')

        appointments.value = response.data.data || []

      } catch (error) {
        toast.error('Failed to load appointments')
        console.error('Error loading appointments:', error)
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      return formatGregorianDate(dateString, locale.value, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const formatTime = (timeString) => {
      if (!timeString) return 'N/A'
      
      // Handle both "09:00" and "09:00:00" formats
      const timeParts = timeString.split(':')
      const hours = parseInt(timeParts[0])
      const minutes = parseInt(timeParts[1])
      
      if (isNaN(hours) || isNaN(minutes)) {
        return 'N/A'
      }
      
      const isArabic = locale.value === 'ar'
      const ampm = isArabic ? (hours >= 12 ? 'م' : 'ص') : (hours >= 12 ? 'PM' : 'AM')
      const displayHour = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
      const formattedMinutes = minutes.toString().padStart(2, '0')
      
      return `${displayHour}:${formattedMinutes} ${ampm}`
    }

    const getStatusText = (status) => {
      const statusMap = {
        'pending': $t('appointmentsPage.statusPending'),
        'confirmed': $t('appointmentsPage.statusConfirmed'),
        'open': $t('appointmentsPage.statusConfirmed'), // Map 'open' to 'confirmed' for display
        'completed': $t('appointmentsPage.statusCompleted'),
        'cancelled': $t('appointmentsPage.statusCancelled'),
        'no_show': $t('appointmentsPage.statusNoShow')
      }
      return statusMap[status] || status
    }

    const getStatusClass = (status) => {
      const classMap = {
        'pending': 'text-yellow-600 bg-yellow-100',
        'confirmed': 'text-green-600 bg-green-100',
        'open': 'text-green-600 bg-green-100', // Map 'open' to 'confirmed' styling
        'completed': 'text-blue-600 bg-blue-100',
        'cancelled': 'text-red-600 bg-red-100',
        'no_show': 'text-gray-600 bg-gray-100'
      }
      return `px-2 py-1 rounded-full text-xs ${classMap[status] || ''}`
    }

    const canJoinSession = (appointment) => {
      // Allow joining for 'open' and 'confirmed' statuses only
      if (appointment.status !== 'confirmed' && appointment.status !== 'open') {
        return false
      }
      
      // Don't show button for completed appointments
      if (appointment.status === 'completed') {
        return false
      }
      
      // Show the button for confirmed/open appointments
      return true
    }

    const canReschedule = (appointment) => {
      // Check status - allow reschedule for confirmed, open, and pending appointments
      if (appointment.status !== 'confirmed' && appointment.status !== 'open' && appointment.status !== 'pending') {
        return false
      }
      
      // Check if appointment has already been rescheduled
      if (appointment.settings && appointment.settings.includes('ai_booking:rescheduled')) {
        return false
      }
      
      // Parse appointment time - try multiple date formats
      let appointmentTime
      if (appointment.date_time) {
        appointmentTime = new Date(appointment.date_time)
      } else if (appointment.date && appointment.time) {
        // Handle both Y-m-d and full datetime formats
        const dateStr = appointment.date.includes(' ') ? appointment.date : `${appointment.date}T${appointment.time}`
        appointmentTime = new Date(dateStr)
      } else if (appointment.date) {
        appointmentTime = new Date(appointment.date)
      } else {
        return false
      }
      
      // Check if date is valid
      if (isNaN(appointmentTime.getTime())) {
        return false
      }
      
      const now = new Date()
      const timeDiff = appointmentTime - now
      
      // Can reschedule only if more than 24 hours before the appointment
      // 24 hours = 24 * 60 * 60 * 1000 milliseconds
      return timeDiff > (24 * 60 * 60 * 1000)
    }

    const canCancel = (appointment) => {
      if (appointment.status !== 'confirmed' && appointment.status !== 'open' && appointment.status !== 'pending') return false
      
      let appointmentTime
      if (appointment.date && appointment.date.includes(' ')) {
        appointmentTime = new Date(appointment.date)
      } else if (appointment.date_time) {
        appointmentTime = new Date(appointment.date_time)
      } else if (appointment.date && appointment.time) {
        appointmentTime = new Date(`${appointment.date}T${appointment.time}`)
      } else {
        return false
      }
      
      const now = new Date()
      
      // Can cancel up to 24 hours before
      return appointmentTime - now > 24 * 60 * 60 * 1000
    }

    const joinSession = async (appointmentId) => {
      // Open session with direct Jitsi integration
      currentSessionId.value = appointmentId
      showSessionModal.value = true
      jitsiLoaded.value = false
      
      // Wait for modal to be rendered before initializing Jitsi
      await nextTick()
      setTimeout(() => {
        initializeSessionJitsi()
      }, 200) // Give modal time to fully render
    }

    const closeSessionModal = (redirectUrl = null) => {
      showSessionModal.value = false
      // Stop logo hiding polling
      stopLogoHidePolling()
      // Clean up Jitsi meeting
      if (sessionMeetAPI.value) {
        sessionMeetAPI.value.dispose()
        sessionMeetAPI.value = null
      }
      jitsiLoaded.value = false
      currentSessionId.value = null
      
      // Redirect if URL is provided
      if (redirectUrl) {
        router.push(redirectUrl)
      }
    }

    const confirmCloseSessionModal = async () => {
      if (jitsiLoaded.value) {
        const result = await Swal.fire({
          title: $t('session.meetingRoom'),
          text: $t('session.confirmCloseModal'),
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc2626',
          cancelButtonColor: '#6b7280',
          confirmButtonText: $t('common.confirm') || 'OK',
          cancelButtonText: $t('common.cancel') || 'Cancel'
        })
        if (result.isConfirmed) {
          closeSessionModal()
        }
      } else {
        closeSessionModal()
      }
    }

    const exitSession = async () => {
      const result = await Swal.fire({
        title: $t('session.exitSession'),
        text: $t('session.confirmExit'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: $t('session.exitSession'),
        cancelButtonText: $t('common.cancel') || 'Cancel'
      })
      if (result.isConfirmed) {
        closeSessionModal('/appointments')
      }
    }

    // Function to hide Jitsi logo using CSS injection
    const hideJitsiLogo = (containerId = '#session-meeting', apiInstance = null) => {
      try {
        const meetingContainer = document.querySelector(containerId)
        if (!meetingContainer) return
        
        const iframe = meetingContainer.querySelector('iframe')
        if (!iframe) return
        
        // Try direct DOM manipulation if iframe is accessible (same origin)
        try {
          const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document
          if (iframeDoc) {
            const css = `
              .watermark,
              .leftwatermark,
              .rightwatermark,
              [class*="watermark"],
              [class*="jitsi-logo"],
              [id*="watermark"],
              [id*="jitsi-logo"],
              .powered-by,
              [class*="poweredby"],
              [data-testid*="watermark"],
              [data-testid*="logo"],
              a.watermark,
              div.watermark,
              a[class*="watermark"],
              div[class*="watermark"] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                height: 0 !important;
                width: 0 !important;
                overflow: hidden !important;
                pointer-events: none !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
              }
            `
            
            // Create and inject style element
            let style = iframeDoc.getElementById('jalsah-hide-logo-style')
            if (!style) {
              style = iframeDoc.createElement('style')
              style.id = 'jalsah-hide-logo-style'
              style.textContent = css
              iframeDoc.head.appendChild(style)
            } else {
              style.textContent = css
            }
            
            // Also directly hide elements as fallback
            const watermarkElements = iframeDoc.querySelectorAll('.watermark, .leftwatermark, .rightwatermark, [class*="watermark"]')
            watermarkElements.forEach(el => {
              el.style.display = 'none'
              el.style.visibility = 'hidden'
              el.style.opacity = '0'
              el.style.height = '0'
              el.style.width = '0'
            })
          }
        } catch (e) {
          // CORS error - expected for cross-origin iframes
          // Try using postMessage as fallback
          try {
            if (iframe.contentWindow) {
              iframe.contentWindow.postMessage({
                type: 'hideWatermark',
                action: 'hide'
              }, '*')
            }
          } catch (postError) {
            // Cannot hide logo due to CORS restrictions
          }
        }
      } catch (error) {
        console.log('Error hiding Jitsi logo:', error)
      }
    }
    
    // Set up continuous polling to hide logo (for cross-origin iframes)
    let logoHideInterval = null
    const startLogoHidePolling = (containerId = '#session-meeting') => {
      if (logoHideInterval) return
      
      logoHideInterval = setInterval(() => {
        hideJitsiLogo(containerId)
      }, 500) // Check every 500ms
    }
    
    const stopLogoHidePolling = () => {
      if (logoHideInterval) {
        clearInterval(logoHideInterval)
        logoHideInterval = null
      }
    }

    const initializeSessionJitsi = () => {
      // Check if modal and container are ready
      const checkContainer = () => {
        const container = document.querySelector('#session-meeting')
        if (!container) {
          return false
        }
        return true
      }
      
      // Wait for container to be available
      const waitForContainer = () => {
        if (checkContainer()) {
          loadJitsiScript()
        } else {
          setTimeout(waitForContainer, 50) // Check every 50ms
        }
      }
      
      const loadJitsiScript = () => {
        // Check if JitsiMeetExternalAPI is already available
        if (typeof JitsiMeetExternalAPI !== 'undefined') {
          startSessionJitsiMeeting()
          return
        }
        
        // Load Jitsi external API script
        const script = document.createElement('script')
        script.src = 'https://s.jalsah.app/external_api.js'
        script.onload = () => {
          setTimeout(() => {
            startSessionJitsiMeeting()
          }, 500) // Give it a moment to initialize
        }
        script.onerror = (error) => {
          console.error('❌ Failed to load Jitsi script:', error)
          toast.error('Failed to load meeting interface')
          jitsiLoaded.value = false
        }
        document.head.appendChild(script)
      }
      
      // Start waiting for container
      waitForContainer()
    }

    const startSessionJitsiMeeting = () => {
      if (!currentSessionId.value) return
      
      // Wait for DOM element to be available
      const meetingContainer = document.querySelector('#session-meeting')
      if (!meetingContainer) {
        console.error('Session meeting container not found')
        toast.error('Failed to initialize meeting room')
        return
      }
      
      const roomID = currentSessionId.value
      const userName = authStore.user?.name || authStore.user?.username || 'User'
      const isTherapist = authStore.user?.role === 'doctor' || authStore.user?.role === 'therapist'
      
      const options = {
        parentNode: meetingContainer,
        roomName: `${roomID} جلسة`,
        width: '100%',
        height: '100%',
        configOverwrite: {
          prejoinPageEnabled: false,
          startWithAudioMuted: false,
          startWithVideoMuted: false,
          disableAudioLevels: false,
          enableClosePage: true,
          enableWelcomePage: false,
          participantsPane: {
            enabled: true,
            hideModeratorSettingsTab: false,
            hideMoreActionsButton: false,
            hideMuteAllButton: false
          },
          toolbarButtons: [
            'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
            'fodeviceselection', 'hangup', 'profile', 'chat', 
            'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
            'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
          ]
        },
        interfaceConfigOverwrite: {
          prejoinPageEnabled: false,
          APP_NAME: 'Jalsah AI',
          DEFAULT_BACKGROUND: "#1a1a1a",
          SHOW_JITSI_WATERMARK: false,
          HIDE_DEEP_LINKING_LOGO: true,
          SHOW_BRAND_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
          SHOW_POWERED_BY: false,
          DISPLAY_WELCOME_FOOTER: false,
          HIDE_INVITE_MORE_HEADER: true,
          JITSI_WATERMARK_LINK: '',
          PROVIDER_NAME: 'Jalsah',
          DEFAULT_LOGO_URL: '',
          DEFAULT_WELCOME_PAGE_LOGO_URL: '',
          TOOLBAR_ALWAYS_VISIBLE: true,
          TOOLBAR_BUTTONS: [
            'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
            'fodeviceselection', 'hangup', 'profile', 'chat', 
            'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
            'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
          ]
        }
      }
      
      try {
        // Try the main Jitsi server first
        try {
          sessionMeetAPI.value = new JitsiMeetExternalAPI("s.jalsah.app", options)
        } catch (serverError) {
          console.warn('⚠️ Main server failed, trying fallback:', serverError)
          // Fallback to meet.jit.si if main server fails
          sessionMeetAPI.value = new JitsiMeetExternalAPI("meet.jit.si", options)
        }
        
        sessionMeetAPI.value.executeCommand('displayName', userName)
        
        // Add event listeners
        sessionMeetAPI.value.addListener('videoConferenceJoined', () => {
          jitsiLoaded.value = true
          
          // Start continuous polling to hide logo
          startLogoHidePolling('#session-meeting')
          
          // Hide Jitsi logo after meeting loads - try multiple times
          setTimeout(() => {
            hideJitsiLogo('#session-meeting', sessionMeetAPI.value)
          }, 500)
          setTimeout(() => {
            hideJitsiLogo('#session-meeting', sessionMeetAPI.value)
          }, 1500)
          setTimeout(() => {
            hideJitsiLogo('#session-meeting', sessionMeetAPI.value)
          }, 3000)
          
          // If therapist joined, notify the backend
          if (isTherapist) {
            notifyTherapistJoined(roomID)
          }
        })
        
        sessionMeetAPI.value.addListener('videoConferenceLeft', () => {
          stopLogoHidePolling()
          closeSessionModal('/appointments')
        })
        
        sessionMeetAPI.value.addListener('readyToClose', () => {
          stopLogoHidePolling()
          closeSessionModal('/appointments')
        })
        
        // Set a timeout to show the meeting even if not fully loaded
        setTimeout(() => {
          if (!jitsiLoaded.value) {
            jitsiLoaded.value = true
          }
        }, 10000) // 10 seconds timeout
        
      } catch (error) {
        console.error('❌ Error initializing Jitsi meeting:', error)
        toast.error('Failed to start meeting')
        jitsiLoaded.value = false
      }
    }

    const notifyTherapistJoined = async (roomID) => {
      try {
        await api.post(`/wp-json/jalsah-ai/v1/session/${roomID}/therapist-join`)
      } catch (error) {
        console.error('Failed to notify therapist joined:', error)
      }
    }

    const rescheduleAppointment = (appointmentId) => {
      router.push(`/booking/reschedule/${appointmentId}`)
    }

    const cancelAppointment = (appointmentId) => {
      appointmentToCancel.value = appointmentId
      showCancelModal.value = true
    }

    const confirmCancel = async () => {
      if (!appointmentToCancel.value) return
      
      cancelling.value = true
      
      try {
        await api.put(`/api/ai/appointments/${appointmentToCancel.value}/cancel`)
        
        toast.success('Appointment cancelled successfully')
        
        // Reload appointments
        await loadAppointments()
        
        showCancelModal.value = false
        appointmentToCancel.value = null
        
      } catch (error) {
        toast.error('Failed to cancel appointment')
        console.error('Error cancelling appointment:', error)
      } finally {
        cancelling.value = false
      }
    }

    // Simulate therapist joining (testing helper)
    const simulateTherapistJoin = (appointment) => {
      if (!appointment) return
      appointment.therapist_joined = true
      toast.success($t('appointmentsPage.joinSession'))
    }

    const bookWithSameTherapist = (appointment) => {
      // Try to find the therapist ID from various possible fields
      const therapistId = appointment.therapist_id || appointment.user_id || appointment.therapist?.id
      
      // Create therapist object for the booking section
      selectedTherapist.value = {
        user_id: therapistId,
        name: appointment.therapist?.name || 'Unknown Therapist',
        photo: appointment.therapist?.photo
      }
      
      // Show the booking modal and load available dates
      showBookingModal.value = true
      loadAvailableDates()
    }

    const closeBookingModal = () => {
      showBookingModal.value = false
      selectedTherapist.value = null
      selectedDate.value = null
      timeSlots.value = []
      availableDates.value = []
    }

    const loadAvailableDates = async () => {
      if (!selectedTherapist.value?.user_id) return
      
      console.log('📅 Loading available dates for:', {
        therapist_id: selectedTherapist.value.user_id,
        attendance_type: 'online'
      })
      
      loadingDates.value = true
      try {
        const response = await api.get('/api/ai/therapist-available-dates', {
          params: {
            therapist_id: selectedTherapist.value.user_id,
            attendance_type: 'online'
          }
        })
        
        console.log('📅 Available dates API response:', response.data)
        
        
        if (response.data.success && response.data.data && response.data.data.available_dates) {
          // The API already respects form_days_count from doctor settings
          // so we get the correct number of dates (up to the doctor's limit)
          const dates = response.data.data.available_dates.map(dateInfo => {
            const date = new Date(dateInfo.date)
            const isArabic = locale.value === 'ar'
            
            let formattedDate
            if (isArabic) {
              // Arabic formatting with day name
              const arabicDays = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
              const arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
              
              const dayName = arabicDays[date.getDay()]
              const monthName = arabicMonths[date.getMonth()]
              const dayNumber = date.getDate()
              
              formattedDate = `${dayName}، ${dayNumber} ${monthName}`
            } else {
              // English formatting with day name
              formattedDate = date.toLocaleDateString('en-US', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
              })
            }
            
            return {
              value: dateInfo.date,
              date: formattedDate,
              isAvailable: true
            }
          })
          availableDates.value = dates
        } else {
          availableDates.value = []
        }
      } catch (error) {
        console.error('Error loading available dates:', error)
        availableDates.value = []
      } finally {
        loadingDates.value = false
      }
    }

    const selectDate = async (date) => {
      selectedDate.value = date
      await loadTimeSlots(date.value)
    }

    const loadTimeSlots = async (date) => {
      if (!selectedTherapist.value?.user_id || !date) return
      
      console.log('🕒 Loading time slots for:', {
        therapist_id: selectedTherapist.value.user_id,
        date: date,
        attendance_type: 'online'
      })
      
      bookingLoadingSlots.value = true
      try {
        const response = await api.get('/api/ai/therapist-availability', {
          params: {
            therapist_id: selectedTherapist.value.user_id,
            date: date,
            attendance_type: 'online'
          }
        })
        
        console.log('🕒 Time slots API response:', response.data)
        
        if (response.data.success && response.data.data && response.data.data.available_slots) {
          timeSlots.value = response.data.data.available_slots
        } else {
          timeSlots.value = []
        }
      } catch (error) {
        console.error('Error loading time slots:', error)
        timeSlots.value = []
      } finally {
        bookingLoadingSlots.value = false
      }
    }

    const addToCart = async (slot) => {
      if (!authStore.isAuthenticated) {
        toast.error($t('common.pleaseLogin'))
        return
      }
      
      cartLoading.value[slot.slot_id] = true
      try {
        const result = await cartStore.addToCart({
          slot_id: slot.slot_id,
          user_id: authStore.user.id
        })
        
        if (result.success) {
          slot.inCart = true
          toast.success($t('therapistDetails.appointmentAdded'), {
            timeout: 8000 // 8 seconds for lengthy message
          })
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else if (result.requiresConfirmation) {
          // Show confirmation dialog for different therapist
          const confirmed = await showDifferentTherapistConfirmation($t('therapistDetails.differentTherapistMessage'))
          if (confirmed) {
            // User confirmed, add to cart with confirmation
            const confirmResult = await cartStore.addToCartWithConfirmation({
              slot_id: slot.slot_id,
              user_id: authStore.user.id
            })
            
            if (confirmResult.success) {
              slot.inCart = true
              toast.success($t('therapistDetails.appointmentAdded'), {
                timeout: 8000 // 8 seconds for lengthy message
              })
              // Emit event to update cart
              window.dispatchEvent(new CustomEvent('cart-updated'))
            } else {
              toast.error(confirmResult.message || $t('common.error'))
            }
          }
        } else {
          toast.error(result.message || $t('common.error'))
        }
      } catch (error) {
        console.error('Error adding to cart:', error)
        toast.error($t('common.error'))
      } finally {
        cartLoading.value[slot.slot_id] = false
      }
    }

    const removeFromCart = async (slot) => {
      if (!authStore.isAuthenticated) {
        toast.error($t('common.pleaseLogin'))
        return
      }
      
      cartLoading.value[slot.slot_id] = true
      try {
        const result = await cartStore.removeFromCart({
          slot_id: slot.slot_id,
          user_id: authStore.user.id
        })
        
        if (result.success) {
          slot.inCart = false
          toast.success($t('therapistDetails.appointmentRemoved'))
          // Emit event to update cart
          window.dispatchEvent(new CustomEvent('cart-updated'))
        } else {
          toast.error(result.message || $t('common.error'))
        }
      } catch (error) {
        console.error('Error removing from cart:', error)
        toast.error($t('common.error'))
      } finally {
        cartLoading.value[slot.slot_id] = false
      }
    }

    const showDifferentTherapistConfirmation = async (message) => {
      try {
        const result = await Swal.fire({
          title: $t('therapistDetails.differentTherapistTitle'),
          text: message,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: $t('common.yes'),
          cancelButtonText: $t('common.cancel')
        })
        return result.isConfirmed
      } catch (error) {
        console.error('Error showing confirmation:', error)
        return false
      }
    }

    const formatTimeSlot = (time) => {
      if (!time) return ''
      
      // Handle both "09:00" and "09:00:00" formats
      const timeParts = time.split(':')
      const hours = parseInt(timeParts[0])
      const minutes = parseInt(timeParts[1])
      
      if (isNaN(hours) || isNaN(minutes)) {
        return time // Return original if parsing fails
      }
      
      const period = hours >= 12 ? 'م' : 'ص'
      const displayHours = hours > 12 ? hours - 12 : hours === 0 ? 12 : hours
      const formattedMinutes = minutes.toString().padStart(2, '0')
      
      return `${displayHours}:${formattedMinutes} ${period}`
    }

    // Therapist join status polling functions
    const startPolling = () => {
      if (isPolling.value) return
      
      // Double-check if we still need polling before starting
      if (!hasUpcomingAppointments.value) return
      
      isPolling.value = true
      pollingInterval.value = setInterval(async () => {
        // Check if we still need to poll before making API call
        if (!hasUpcomingAppointments.value) {
          stopPolling()
          return
        }
        await checkTherapistJoinStatus()
      }, 5000) // Check every 5 seconds
    }

    const stopPolling = () => {
      if (pollingInterval.value) {
        clearInterval(pollingInterval.value)
        pollingInterval.value = null
      }
      isPolling.value = false
    }

    const checkTherapistJoinStatus = async () => {
      try {
        // If no appointments need polling, don't make API call
        if (appointmentsNeedingPolling.value.length === 0) {
          stopPolling()
          return
        }

        const response = await api.get('/api/ai/appointments')
        const updatedAppointments = response.data.data || []
        
        // Only update therapist_joined status for appointments that haven't been joined yet
        appointmentsNeedingPolling.value.forEach((appointment) => {
          const appointmentIndex = appointments.value.findIndex(apt => apt.id === appointment.id)
          if (appointmentIndex !== -1) {
            const updatedAppointment = updatedAppointments.find(updated => updated.id === appointment.id)
            if (updatedAppointment && updatedAppointment.therapist_joined) {
              appointments.value[appointmentIndex].therapist_joined = updatedAppointment.therapist_joined
            }
          }
        })
      } catch (error) {
        console.error('Error checking therapist join status:', error)
      }
    }

    const hasUpcomingAppointments = computed(() => {
      return appointments.value.some(appointment => 
        (appointment.status === 'confirmed' || appointment.status === 'open' || appointment.status === 'pending') &&
        !appointment.therapist_joined
      )
    })

    // Track appointments that still need therapist join status checking
    const appointmentsNeedingPolling = computed(() => {
      return appointments.value.filter(appointment => 
        (appointment.status === 'confirmed' || appointment.status === 'open' || appointment.status === 'pending') &&
        !appointment.therapist_joined
      )
    })

    // Watch for upcoming appointments to start/stop polling
    watch(hasUpcomingAppointments, (hasUpcoming) => {
      if (hasUpcoming) {
        startPolling()
      } else {
        stopPolling()
      }
    }, { immediate: true })

    // Load prescription requests
    const loadPrescriptionRequests = async () => {
      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/prescription-requests', {
          params: {
            user_id: authStore.user?.id,
            locale: locale.value
          }
        })
        prescriptionRequests.value = response.data.data || []
      } catch (error) {
        // Don't log aborted requests (they're cancelled intentionally)
        if (error.code !== 'ECONNABORTED') {
          console.error('Error loading prescription requests:', error)
        }
      }
    }

    // Load completed prescriptions
    const loadCompletedPrescriptions = async () => {
      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/completed-prescriptions', {
          params: {
            user_id: authStore.user?.id,
            locale: locale.value
          }
        })
        completedPrescriptions.value = response.data.data || []
      } catch (error) {
        console.error('Error loading completed prescriptions:', error)
      }
    }

    // Show Rochtah booking modal
    const showRochtahBookingModal = async (requestId) => {
      currentRequestId.value = requestId
      showRochtahModal.value = true
      loadingSlots.value = true
      selectedSlot.value = null
      
      try {
        const response = await api.get('/wp-json/jalsah-ai/v1/rochtah-available-slots', {
          params: {
            request_id: requestId
          }
        })
        
        if (response.data.success) {
          // Sort slots by date and time (nearest first)
          const slots = response.data.data || []
          availableSlots.value = slots.sort((a, b) => {
            const dateA = new Date(`${a.date} ${a.time}`)
            const dateB = new Date(`${b.date} ${b.time}`)
            return dateA - dateB
          })
          
          // Debug: Log sorted slots
          console.log('[Rochtah Frontend] Total slots after sort:', availableSlots.value.length)
          availableSlots.value.slice(0, 5).forEach((slot, index) => {
            console.log(`[Rochtah Frontend] Slot ${index}:`, slot.date, slot.time)
          })
        } else {
          toast.error(response.data.message || 'Failed to load available slots')
        }
      } catch (error) {
        toast.error('Failed to load available slots')
        console.error('Error loading Rochtah slots:', error)
      } finally {
        loadingSlots.value = false
      }
    }

    // Close Rochtah modal
    const closeRochtahModal = () => {
      showRochtahModal.value = false
      selectedSlot.value = null
      availableSlots.value = []
      currentRequestId.value = null
    }

    // Select a time slot
    const selectSlot = (slot) => {
      selectedSlot.value = slot
    }

    // Show booking confirmation
    const bookRochtahAppointment = () => {
      if (!selectedSlot.value) return
      showBookingConfirmModal.value = true
    }

    // View Rochtah appointment details
    const viewRochtahAppointment = (requestId) => {
      // Find the request
      const request = prescriptionRequests.value.find(r => r.id === requestId)
      if (request) {
        toast.info(`Appointment scheduled for ${formatDate(request.booking_date)} at ${formatTime(request.booking_time)}`)
      }
    }

    // Confirm and book Rochtah appointment
    const confirmRochtahBooking = async () => {
      if (!selectedSlot.value || !currentRequestId.value) return
      
      bookingRochtah.value = true
      
      try {
        const response = await api.post('/wp-json/jalsah-ai/v1/rochtah-book-appointment', {
          request_id: currentRequestId.value,
          selected_date: selectedSlot.value.date,
          selected_time: selectedSlot.value.time
        })
        
        if (response.data.success) {
          toast.success(response.data.data.message || 'Appointment booked successfully')
          
          // Close modals
          showBookingConfirmModal.value = false
          closeRochtahModal()
          
          // Reload prescription requests
          await loadPrescriptionRequests()
          
          // Reload appointments to show the new Rochtah appointment
          await loadAppointments()
        } else {
          toast.error(response.data.message || 'Failed to book appointment')
        }
      } catch (error) {
        toast.error('Failed to book appointment')
        console.error('Error booking Rochtah appointment:', error)
      } finally {
        bookingRochtah.value = false
      }
    }

    // Join Rochtah meeting
    const joinRochtahMeeting = async (requestId) => {
      try {
        // Get the prescription request to find the booking ID
        const request = prescriptionRequests.value.find(r => r.id === requestId)
        if (!request || !request.booking_id) {
          toast.error('No booking found for this appointment')
          return
        }

        // Get meeting details
        const response = await api.get('/wp-json/jalsah-ai/v1/rochtah-meeting-details', {
          params: {
            booking_id: request.booking_id
          }
        })

        if (response.data.success) {
          const meetingDetails = response.data.data
          
          // Store meeting details for the session modal
          rochtahMeetingDetails.value = meetingDetails
          showRochtahSessionModal.value = true
          
          // Auto-start the meeting when modal opens
          setTimeout(() => {
            startRochtahMeeting()
          }, 300) // Small delay to ensure modal is rendered
        } else {
          toast.error(response.data.message || 'Failed to get meeting details')
        }
      } catch (error) {
        toast.error('Failed to join meeting')
        console.error('Error joining Rochtah meeting:', error)
      }
    }

    // Close Rochtah session modal and cleanup
    const closeRochtahSessionModal = () => {
      // Stop logo hiding polling
      stopLogoHidePolling()
      // Clean up meeting API
      if (rochtahMeetingAPI.value) {
        try {
          rochtahMeetingAPI.value.dispose()
        } catch (e) {
          console.warn('Error disposing rochtah meeting:', e)
        }
        rochtahMeetingAPI.value = null
      }
      showRochtahSessionModal.value = false
      rochtahMeetingDetails.value = null
    }
    
    // Start Rochtah meeting
    const startRochtahMeeting = () => {
      if (!rochtahMeetingDetails.value) return
      
      // Check if JitsiMeetExternalAPI is already available
      if (typeof JitsiMeetExternalAPI !== 'undefined') {
        initializeRochtahJitsiMeeting()
        return
      }
      
      // Load Jitsi external API script
      const script = document.createElement('script')
      script.src = 'https://s.jalsah.app/external_api.js'
      script.onload = () => {
        setTimeout(() => {
          initializeRochtahJitsiMeeting()
        }, 500) // Give it a moment to initialize
      }
      script.onerror = (error) => {
        console.error('❌ Failed to load Jitsi script:', error)
        toast.error('Failed to load meeting interface')
      }
      document.head.appendChild(script)
    }

    // Initialize Rochtah Jitsi meeting
    const initializeRochtahJitsiMeeting = () => {
      if (!rochtahMeetingDetails.value) return
      
      const roomName = rochtahMeetingDetails.value.room_name
      const userName = authStore.user?.name || authStore.user?.username || 'User'
      
      const options = {
        parentNode: document.querySelector('#rochtah-meeting'),
        roomName: roomName,
        width: '100%',
        height: '100%',
        configOverwrite: {
          prejoinPageEnabled: false,
          startWithAudioMuted: false,
          startWithVideoMuted: false,
          disableAudioLevels: false,
          enableClosePage: true,
          enableWelcomePage: false,
          participantsPane: {
            enabled: true,
            hideModeratorSettingsTab: false,
            hideMoreActionsButton: false,
            hideMuteAllButton: false
          },
          toolbarButtons: [
            'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
            'fodeviceselection', 'hangup', 'profile', 'chat', 
            'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
            'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
          ]
        },
        interfaceConfigOverwrite: {
          prejoinPageEnabled: false,
          APP_NAME: 'Jalsah Rochtah',
          DEFAULT_BACKGROUND: "#1a1a1a",
          SHOW_JITSI_WATERMARK: false,
          HIDE_DEEP_LINKING_LOGO: true,
          SHOW_BRAND_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
          SHOW_POWERED_BY: false,
          DISPLAY_WELCOME_FOOTER: false,
          HIDE_INVITE_MORE_HEADER: true,
          JITSI_WATERMARK_LINK: '',
          PROVIDER_NAME: 'Jalsah',
          DEFAULT_LOGO_URL: '',
          DEFAULT_WELCOME_PAGE_LOGO_URL: '',
          TOOLBAR_ALWAYS_VISIBLE: true,
          TOOLBAR_BUTTONS: [
            'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen', 
            'fodeviceselection', 'hangup', 'profile', 'chat', 
            'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand', 
            'videoquality', 'filmstrip', 'feedback', 'stats', 'tileview'
          ]
        }
      }
      
      try {
        // Clean up any existing meeting API
        if (rochtahMeetingAPI.value) {
          try {
            rochtahMeetingAPI.value.dispose()
          } catch (e) {
            console.warn('Error disposing previous meeting:', e)
          }
          rochtahMeetingAPI.value = null
        }
        
        // Try the main Jitsi server first
        let meetAPI
        try {
          meetAPI = new JitsiMeetExternalAPI("s.jalsah.app", options)
        } catch (serverError) {
          console.warn('⚠️ Main server failed, trying fallback:', serverError)
          // Fallback to meet.jit.si if main server fails
          meetAPI = new JitsiMeetExternalAPI("meet.jit.si", options)
        }
        
        // Store the API instance
        rochtahMeetingAPI.value = meetAPI
        
        // Set display name
        meetAPI.executeCommand('displayName', userName)
        
        // Auto-start for patients - listen for when meeting is ready and try to auto-join
        meetAPI.addListener('videoConferenceJoined', () => {
          console.log('Rochtah meeting joined successfully')
          toast.success('Meeting started successfully')
          
          // Start continuous polling to hide logo
          startLogoHidePolling('#rochtah-meeting')
          
          // Hide Jitsi logo after meeting loads - try multiple times
          setTimeout(() => {
            hideJitsiLogo('#rochtah-meeting', meetAPI)
          }, 500)
          setTimeout(() => {
            hideJitsiLogo('#rochtah-meeting', meetAPI)
          }, 1500)
          setTimeout(() => {
            hideJitsiLogo('#rochtah-meeting', meetAPI)
          }, 3000)
        })
        
        // Fallback: Auto-click any join/start button if it appears
        const attemptAutoJoin = () => {
          const startButton = document.querySelector('[data-testid="prejoin.joinMeeting"]') || 
                              document.querySelector('.prejoin-button') ||
                              document.querySelector('button[aria-label*="Join"]') ||
                              document.querySelector('button[aria-label*="join"]') ||
                              document.querySelector('button[aria-label*="ابدأ"]') ||
                              document.querySelector('button[aria-label*="Join meeting"]') ||
                              document.querySelector('[data-tooltip*="Join"]') ||
                              document.querySelector('[id*="join"]') ||
                              document.querySelector('[class*="join-button"]')
          if (startButton && typeof startButton.click === 'function') {
            try {
              startButton.click()
              console.log('Auto-clicked start button for rochtah meeting')
            } catch(e) {
              console.log('Could not auto-click button:', e)
            }
          }
        }
        
        // Try auto-join after delays to catch any delayed button rendering
        setTimeout(attemptAutoJoin, 500)
        setTimeout(attemptAutoJoin, 1000)
        setTimeout(attemptAutoJoin, 2000)
        
      } catch (error) {
        console.error('❌ Error initializing Rochtah Jitsi meeting:', error)
        toast.error('Failed to start meeting')
      }
    }

    // View prescription details
    const viewPrescriptionDetails = (prescription) => {
      selectedPrescription.value = prescription
      showPrescriptionModal.value = true
    }

    // Close prescription modal
    const closePrescriptionModal = () => {
      showPrescriptionModal.value = false
      selectedPrescription.value = null
    }


    // Poll prescription requests to check for doctor joined status
    const prescriptionPollingInterval = ref(null)
    
    const startPrescriptionPolling = () => {
      // Poll every 5 seconds to check if doctor has joined
      prescriptionPollingInterval.value = setInterval(async () => {
        // Only poll if there are confirmed rochtah bookings that haven't been joined yet
        const hasPendingRochtah = prescriptionRequests.value.some(r => 
          r.status === 'confirmed' && 
          r.booking_date && 
          r.booking_date !== '0000-00-00' &&
          (!r.doctor_joined || r.doctor_joined === false)
        )
        
        if (hasPendingRochtah) {
          await loadPrescriptionRequests()
        }
      }, 5000)
    }
    
    const stopPrescriptionPolling = () => {
      if (prescriptionPollingInterval.value) {
        clearInterval(prescriptionPollingInterval.value)
        prescriptionPollingInterval.value = null
      }
    }
    
    onMounted(() => {
      loadAppointments()
      loadPrescriptionRequests()
      loadCompletedPrescriptions()
      
      // Start polling prescription requests to check doctor joined status
      startPrescriptionPolling()
    })
    
    onUnmounted(() => {
      stopPrescriptionPolling()
      
      // Close all open popups when component is unmounted
      openPopups.value.forEach((popup, appointmentId) => {
        if (popup && !popup.closed) {
          popup.close()
        }
      })
      openPopups.value.clear()
      // Clean up Jitsi meeting
      if (sessionMeetAPI.value) {
        sessionMeetAPI.value.dispose()
        sessionMeetAPI.value = null
      }
      // Ensure session modal cleaned
      showSessionModal.value = false
      jitsiLoaded.value = false
      currentSessionId.value = null
      // Stop polling when component unmounts
      stopPolling()
    })

    return {
      loading,
      cancelling,
      showCancelModal,
      appointments,
      activeTab,
      tabs,
      filteredAppointments,
      formatDate,
      formatTime,
      getStatusText,
      getStatusClass,
      canJoinSession,
      canReschedule,
      canCancel,
      joinSession,
      rescheduleAppointment,
      cancelAppointment,
      confirmCancel,
      showSimulate,
      simulateTherapistJoin,
      showSessionModal,
      jitsiLoaded,
      closeSessionModal,
      confirmCloseSessionModal,
      exitSession,
      bookWithSameTherapist,
      // Booking modal related
      showBookingModal,
      selectedTherapist,
      availableDates,
      selectedDate,
      timeSlots,
      loadingDates,
      bookingLoadingSlots,
      cartLoading,
      closeBookingModal,
      loadAvailableDates,
      selectDate,
      loadTimeSlots,
      addToCart,
      removeFromCart,
      showDifferentTherapistConfirmation,
      formatTimeSlot,
      // Auth store
      authStore,
      // Therapist join status polling
      startPolling,
      stopPolling,
      checkTherapistJoinStatus,
      hasUpcomingAppointments,
      appointmentsNeedingPolling,
      isPolling,
      // Rochtah booking related
      prescriptionRequests,
      completedPrescriptions,
      loadCompletedPrescriptions,
      showRochtahModal,
      showBookingConfirmModal,
      loadingSlots,
      bookingRochtah,
      availableSlots,
      selectedSlot,
      showRochtahBookingModal,
      closeRochtahModal,
      joinRochtahMeeting,
      startRochtahMeeting,
      showRochtahSessionModal,
      closeRochtahSessionModal,
      rochtahMeetingDetails,
      selectSlot,
      viewPrescriptionDetails,
      closePrescriptionModal,
      showPrescriptionModal,
      selectedPrescription,
      bookRochtahAppointment,
      confirmRochtahBooking,
      viewRochtahAppointment
    }
  }
}
</script> 