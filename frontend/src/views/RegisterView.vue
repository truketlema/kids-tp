<template>
  <main class="register-page">
    <div class="page">
      <!-- Left brand panel -->
      <aside class="brand-panel" aria-label="Kids TP family benefits">
        <img class="mascot" src="/Logo.png" alt="Kids TP mascot" />

        <div class="brand-copy">
          <p class="eyebrow">
            TRUST POINTS &amp; FINANCIAL RESPONSIBILITY
          </p>

          <article class="benefit">
            <span class="benefit-icon">
              <v-icon icon="mdi-account-group-outline" />
            </span>

            <div>
              <h2>One family, working together</h2>
              <p>Manage multiple children with the parents you invite.</p>
            </div>
          </article>

          <article class="benefit">
            <span class="benefit-icon">
              <v-icon icon="mdi-star-outline" />
            </span>

            <div>
              <h2>Points with purpose</h2>
              <p>
                Recognize completed commitments and build accountability.
              </p>
            </div>
          </article>

          <article class="benefit">
            <span class="benefit-icon">
              <v-icon icon="mdi-wallet-outline" />
            </span>

            <div>
              <h2>A clearer financial picture</h2>
              <p>
                Turn Trust Points into simple ETB planning allocations.
              </p>
            </div>
          </article>
        </div>
      </aside>

      <!-- Registration form panel -->
      <section
        class="form-panel"
        aria-label="Parent registration form"
      >
        <div class="print-decoration" aria-hidden="true">
          <img class="print print-one" src="/print.png" alt="" />
          <img class="print print-two" src="/print.png" alt="" />
        </div>

        <form
          class="registration"
          :aria-busy="isSubmitting"
          @submit.prevent="submitForm"
        >
          <p class="badge">PARENT REGISTRATION</p>

          <h1 class="mobile-title">
            Good habits grow into brighter futures.
          </h1>

          <label class="field">
            <span>Parent name *</span>
            <input
              v-model.trim="form.parentName"
              name="parentName"
              autocomplete="name"
              placeholder="Selam Tesfaye"
              required
            />
          </label>

          <label class="field">
            <span>Email address *</span>
            <input
              v-model.trim="form.email"
              name="email"
              type="email"
              autocomplete="email"
              placeholder="selam.tesfaye@example.com"
              required
            />
          </label>

          <div class="field">
            <label class="field-label" for="phone-number">
              Phone number *
            </label>
            <div class="phone-input">
              <div
                ref="countryPickerElement"
                class="phone-code-picker"
                @keydown.esc.stop="closeCountryList"
              >
                <button
                  class="phone-code-trigger"
                  type="button"
                  :aria-label="`Country calling code ${selectedCallingCode}`"
                  aria-haspopup="listbox"
                  aria-controls="country-code-options"
                  :aria-expanded="isCountryListOpen"
                  @click="toggleCountryList"
                >
                  {{ selectedCallingCode }}
                  <v-icon icon="mdi-chevron-down" aria-hidden="true" />
                </button>

                <div
                  v-if="isCountryListOpen"
                  id="country-code-options"
                  class="phone-country-menu"
                >
                  <input
                    ref="countrySearchInput"
                    v-model.trim="countrySearch"
                    class="country-search"
                    type="search"
                    role="searchbox"
                    aria-label="Search countries or calling codes"
                    placeholder="Search countries"
                    @keydown.enter.prevent
                  />

                  <div
                    class="country-options"
                    role="listbox"
                    aria-label="Countries and calling codes"
                  >
                    <button
                      v-for="country in filteredCountries"
                      :key="country.code"
                      class="country-option"
                      type="button"
                      role="option"
                      :aria-selected="selectedCountry === country.code"
                      @click="selectCountry(country.code)"
                    >
                      <span class="country-option-name">
                        {{ country.name }}
                      </span>
                      <span class="country-option-code">
                        +{{ country.callingCode }}
                      </span>
                    </button>

                    <p
                      v-if="filteredCountries.length === 0"
                      class="country-empty"
                    >
                      No matching country or calling code.
                    </p>
                  </div>
                </div>
              </div>

              <input
                v-model.trim="form.phone"
                id="phone-number"
                name="phone"
                type="tel"
                autocomplete="tel-national"
                inputmode="tel"
                placeholder="Phone number"
                required
              />
            </div>
          </div>

          <label class="field">
            <span>Password *</span>
            <input
              v-model="form.password"
              name="password"
              type="password"
              autocomplete="new-password"
              placeholder="••••••••••••"
              minlength="8"
              required
            />
          </label>

          <label class="field">
            <span>Confirm password *</span>
            <input
              v-model="form.passwordConfirmation"
              name="password_confirmation"
              type="password"
              autocomplete="new-password"
              placeholder="Re-enter your password"
              minlength="8"
              required
            />
          </label>

          <label class="field">
            <span>Family name *</span>
            <input
              v-model.trim="form.familyName"
              name="familyName"
              autocomplete="organization"
              placeholder="Abebe family"
              required
            />
          </label>

          <label class="terms">
            <input
              v-model="form.acceptedTerms"
              type="checkbox"
              required
            />

            <span>
              I agree to the Terms of Service and Privacy Policy.
              I am registering as a parent or guardian.
            </span>
          </label>

          <button class="submit" type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Creating account...' : 'Create parent account & family' }}
          </button>

          <div class="form-footer">
            <span>Already have an account?</span>

            <RouterLink class="login" to="/login">
              Log in →
            </RouterLink>
          </div>

          <p
            class="status"
            :class="{ 'status-error': statusType === 'error' }"
            :role="statusType === 'error' ? 'alert' : 'status'"
            aria-live="polite"
          >
            {{ status }}
          </p>
        </form>
      </section>
    </div>
  </main>
</template>

<script setup>
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'
import {
  getCountries,
  getCountryCallingCode,
  parsePhoneNumberFromString,
} from 'libphonenumber-js'
import { registerAccount } from '../services/auth.js'

const router = useRouter()
const regionNames = new Intl.DisplayNames(['en'], { type: 'region' })
const countries = getCountries()
  .map((code) => ({
    code,
    name: regionNames.of(code) ?? code,
    callingCode: getCountryCallingCode(code),
  }))
  .sort((first, second) => first.name.localeCompare(second.name))
const selectedCountry = ref('ET')
const isCountryListOpen = ref(false)
const countrySearch = ref('')
const countryPickerElement = ref(null)
const countrySearchInput = ref(null)
const selectedCallingCode = computed(() => {
  const country = countries.find(({ code }) => code === selectedCountry.value)
  return country ? `+${country.callingCode}` : ''
})
const filteredCountries = computed(() => {
  const query = countrySearch.value.toLowerCase().replace(/^\+/, '')

  if (!query) {
    return countries
  }

  return countries.filter(({ name, callingCode }) =>
    name.toLowerCase().includes(query) || callingCode.includes(query),
  )
})

const form = reactive({
  parentName: '',
  email: '',
  phone: '',
  password: '',
  passwordConfirmation: '',
  familyName: '',
  acceptedTerms: false,
})

const status = ref('')
const statusType = ref('success')
const isSubmitting = ref(false)

function toggleCountryList() {
  isCountryListOpen.value = !isCountryListOpen.value

  if (isCountryListOpen.value) {
    nextTick(() => countrySearchInput.value?.focus())
  }
}

function closeCountryList() {
  isCountryListOpen.value = false
  countrySearch.value = ''
}

function selectCountry(countryCode) {
  selectedCountry.value = countryCode
  closeCountryList()
}

function handleCountryPickerOutsideClick(event) {
  if (!countryPickerElement.value?.contains(event.target)) {
    closeCountryList()
  }
}

async function submitForm() {
  status.value = ''
  statusType.value = 'success'

  if (form.password !== form.passwordConfirmation) {
    status.value = 'Passwords do not match.'
    statusType.value = 'error'
    return
  }

  const parsedPhone = parsePhoneNumberFromString(
    form.phone,
    selectedCountry.value,
  )

  if (!parsedPhone?.isValid()) {
    status.value = 'Enter a valid phone number for the selected country.'
    statusType.value = 'error'
    return
  }

  isSubmitting.value = true

  try {
    const result = await registerAccount({
      name: form.parentName,
      email: form.email,
      phone: parsedPhone.number,
      password: form.password,
      password_confirmation: form.passwordConfirmation,
      family_name: form.familyName,
    })

    status.value = result.message
    await router.replace({ name: 'dashboard' })
  } catch (error) {
    status.value = error instanceof Error
      ? error.message
      : 'Registration failed due to an unexpected error.'
    statusType.value = 'error'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', handleCountryPickerOutsideClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', handleCountryPickerOutsideClick)
})
</script>

<style scoped src="../assets/styles/register.css"></style>