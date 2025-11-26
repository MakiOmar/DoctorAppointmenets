<template>
  <div>
    <label :for="id" class="form-label">{{ label }}</label>
    <div class="fancy-upload">
      <input
        :id="id"
        type="file"
        :accept="accept"
        :multiple="multiple"
        class="hidden"
        @change="onFileChange"
        ref="fileInput"
      />
      <button type="button" class="upload-btn" @click="triggerFileInput">
        {{ buttonText }}
      </button>
      <div v-if="previews.length" class="previews">
        <div v-for="(file, idx) in previews" :key="idx" class="preview-item">
          <img v-if="file.isImage" :src="file.url" class="preview-img" />
          <div v-else class="preview-file">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7v10a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2z" />
            </svg>
            <span class="file-name">{{ file.name }}</span>
          </div>
          <button type="button" class="remove-btn" @click="removeFile(idx)">&times;</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: { type: [File, Array], default: null },
  label: { type: String, default: '' },
  id: { type: String, default: () => `fancy-upload-${Math.random().toString(36).substr(2, 9)}` },
  accept: { type: String, default: '' },
  multiple: { type: Boolean, default: false },
  buttonText: { type: String, default: 'Choose file(s)' }
})
const emit = defineEmits(['update:modelValue'])
const fileInput = ref(null)
const previews = ref([])

function triggerFileInput() {
  fileInput.value.click()
}

function onFileChange(e) {
  const files = Array.from(e.target.files)
  updateFiles(files)
}

function updateFiles(files) {
  if (!props.multiple) {
    emit('update:modelValue', files[0] || null)
    previews.value = files.length ? [makePreview(files[0])] : []
  } else {
    emit('update:modelValue', files)
    previews.value = files.map(makePreview)
  }
}

function makePreview(file) {
  let url = ''
  let isImage = false
  if (file && file.type.startsWith('image/')) {
    url = URL.createObjectURL(file)
    isImage = true
  }
  return { url, isImage, name: file.name }
}

function removeFile(idx) {
  if (!props.multiple) {
    emit('update:modelValue', null)
    previews.value = []
    fileInput.value.value = ''
  } else {
    const files = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    files.splice(idx, 1)
    emit('update:modelValue', files)
    previews.value.splice(idx, 1)
    if (!files.length) fileInput.value.value = ''
  }
}

// Watch for external modelValue changes (reset, etc.)
watch(() => props.modelValue, (val) => {
  if (!val || (Array.isArray(val) && !val.length)) {
    previews.value = []
    if (fileInput.value) fileInput.value.value = ''
  }
}, { deep: true })
</script>

<style scoped>
.fancy-upload {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.upload-btn {
  background: #f3f4f6;
  color: #2563eb;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}
.upload-btn:hover {
  background: #e0e7ff;
}
.previews {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 0.5rem;
}
.preview-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  padding: 0.5rem;
  background: #fafafa;
}
.preview-img {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: 0.25rem;
  margin-bottom: 0.25rem;
}
.preview-file {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.file-name {
  font-size: 0.85rem;
  color: #374151;
}
.remove-btn {
  position: absolute;
  top: 0;
  right: 0;
  background: #f87171;
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 1rem;
  cursor: pointer;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}
.remove-btn:hover {
  background: #ef4444;
}
</style> 