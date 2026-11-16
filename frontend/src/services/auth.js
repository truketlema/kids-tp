const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || '/api').replace(/\/+$/, '')
const TOKEN_STORAGE_KEY = 'kids-tp-token'

export class ApiError extends Error {
  constructor(message, status) {
    super(message)
    this.name = 'ApiError'
    this.status = status
  }
}

export function getAuthToken() {
  return localStorage.getItem(TOKEN_STORAGE_KEY) || sessionStorage.getItem(TOKEN_STORAGE_KEY)
}

export function clearAuthToken() {
  localStorage.removeItem(TOKEN_STORAGE_KEY)
  sessionStorage.removeItem(TOKEN_STORAGE_KEY)
}

async function request(endpoint, { method = 'GET', payload, authenticated = false } = {}) {
  const headers = {
    Accept: 'application/json',
  }
  const options = { method, headers }

  if (payload !== undefined) {
    headers['Content-Type'] = 'application/json'
    options.body = JSON.stringify(payload)
  }

  if (authenticated) {
    const token = getAuthToken()

    if (!token) {
      throw new ApiError('You need to sign in to continue.', 401)
    }

    headers.Authorization = `Bearer ${token}`
  }

  let response

  try {
    response = await fetch(`${API_BASE_URL}${endpoint}`, options)
  } catch (error) {
    if (error instanceof TypeError) {
      throw new Error('Unable to reach the API. Make sure the Laravel server is running.')
    }

    throw error
  }

  const responseBody = await response.text()
  let data

  if (responseBody) {
    try {
      data = JSON.parse(responseBody)
    } catch {
      throw new ApiError(
        `The API returned an unreadable response (HTTP ${response.status}). Check that the Laravel server is configured and inspect its logs.`,
        response.status,
      )
    }
  }

  if (!data || typeof data !== 'object') {
    const message = response.ok
      ? 'The API returned an empty or invalid response.'
      : `The API returned an empty response (HTTP ${response.status}). Check that the Laravel server is configured and inspect its logs.`

    throw new ApiError(message, response.status)
  }

  if (!response.ok) {
    const validationMessages = Object.values(data.errors ?? {})
      .flat()
      .filter((message) => typeof message === 'string')
    const serverMessage = typeof data.message === 'string' ? data.message : ''

    throw new ApiError(
      validationMessages.join(' ') ||
      serverMessage ||
      `The API request failed with status ${response.status}.`,
      response.status,
    )
  }

  return data
}

async function authenticate(endpoint, payload, remember = true) {
  const data = await request(endpoint, {
    method: 'POST',
    payload,
  })

  if (typeof data.token !== 'string' || data.token.length === 0) {
    throw new Error('The API response did not include an authentication token.')
  }

  clearAuthToken()
  const storage = remember ? localStorage : sessionStorage
  storage.setItem(TOKEN_STORAGE_KEY, data.token)

  return data
}

export function registerAccount(payload) {
  return authenticate('/register', payload)
}

export function loginAccount(payload, remember = false) {
  return authenticate('/login', payload, remember)
}

export function getCurrentUser() {
  return request('/user', { authenticated: true })
}

export function fetchChildren() {
  return request('/children', { authenticated: true })
}

export function addChild(payload) {
  return request('/children', {
    method: 'POST',
    payload,
    authenticated: true,
  })
}
