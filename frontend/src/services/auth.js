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
      throw new Error('Unable to reach the API. Make sure the Laravel server is running.', {
        cause: error,
      })
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

export function updateProfile(payload) {
  return request('/user/profile', {
    method: 'PATCH',
    payload,
    authenticated: true,
  })
}

export function verifyEmailChange(code) {
  return request('/user/email/verify', {
    method: 'POST',
    payload: { code },
    authenticated: true,
  })
}

export function changePassword(payload) {
  return request('/user/password', {
    method: 'POST',
    payload,
    authenticated: true,
  })
}

export function deleteAccount(password) {
  return request('/user', {
    method: 'DELETE',
    payload: { password },
    authenticated: true,
  })
}

export function fetchNotificationPreferences() {
  return request('/user/notification-preferences', { authenticated: true })
}

export function updateNotificationPreferences(payload) {
  return request('/user/notification-preferences', {
    method: 'PATCH',
    payload,
    authenticated: true,
  })
}

export function fetchNotifications() {
  return request('/notifications', { authenticated: true })
}

export function markNotificationRead(notificationId) {
  return request(`/notifications/${encodeURIComponent(notificationId)}/read`, {
    method: 'PATCH',
    authenticated: true,
  })
}

export function markAllNotificationsRead() {
  return request('/notifications/read-all', {
    method: 'PATCH',
    authenticated: true,
  })
}

export function fetchChildren() {
  return request('/children', { authenticated: true })
}

export function fetchChildLedger(childId) {
  return request(`/children/${encodeURIComponent(childId)}/ledger`, { authenticated: true })
}

export function fetchFamilyLedger(filters = {}) {
  const query = new URLSearchParams()
  if (filters.type) query.set('type', filters.type)
  if (filters.childId) query.set('child_id', filters.childId)
  const suffix = query.size ? `?${query.toString()}` : ''

  return request(`/ledger${suffix}`, { authenticated: true })
}

export function addLedgerEntry(childId, payload) {
  return request(`/children/${encodeURIComponent(childId)}/ledger`, {
    method: 'POST',
    payload,
    authenticated: true,
  })
}

export function fetchCategories() {
  return request('/categories', { authenticated: true })
}

export function addCategory(payload) {
  return request('/categories', {
    method: 'POST',
    payload,
    authenticated: true,
  })
}

export function deleteCategory(categoryId) {
  return request(`/categories/${encodeURIComponent(categoryId)}`, {
    method: 'DELETE',
    authenticated: true,
  })
}

export function addCategoryActivity(categoryId, payload) {
  return request(`/categories/${encodeURIComponent(categoryId)}/activities`, {
    method: 'POST',
    payload,
    authenticated: true,
  })
}

export function updateActivity(activityId, payload) {
  return request(`/activities/${encodeURIComponent(activityId)}`, {
    method: 'PATCH',
    payload,
    authenticated: true,
  })
}

export function assignChildActivity(childId, activityId, payload) {
  return request(
    `/children/${encodeURIComponent(childId)}/activities/${encodeURIComponent(activityId)}`,
    {
      method: 'POST',
      payload,
      authenticated: true,
    },
  )
}

export function updateChildActivityTp(childId, activityId, payload) {
  return request(
    `/children/${encodeURIComponent(childId)}/activities/${encodeURIComponent(activityId)}`,
    {
      method: 'PATCH',
      payload,
      authenticated: true,
    },
  )
}

export function deleteActivity(activityId) {
  return request(`/activities/${encodeURIComponent(activityId)}`, {
    method: 'DELETE',
    authenticated: true,
  })
}

export function addChild(payload) {
  return request('/children', {
    method: 'POST',
    payload,
    authenticated: true,
  })
}

export function updateFamily(payload) {
  return request('/family', {
    method: 'PATCH',
    payload,
    authenticated: true,
  })
}

export function inviteParent(email) {
  return request('/family/invitations', {
    method: 'POST',
    payload: { email },
    authenticated: true,
  })
}

export function getFamilyInvitation(token) {
  return request(`/invitations/${encodeURIComponent(token)}`)
}

export function acceptFamilyInvitation(payload) {
  return authenticate('/invitations/accept', payload)
}

export function updateChild(childId, payload) {
  return request(`/children/${encodeURIComponent(childId)}`, {
    method: 'PATCH',
    payload,
    authenticated: true,
  })
}

export function removeChild(childId) {
  return request(`/children/${encodeURIComponent(childId)}`, {
    method: 'DELETE',
    authenticated: true,
  })
}

export function updateChildAllocation(childId, allocation) {
  return request(`/children/${encodeURIComponent(childId)}/allocation`, {
    method: 'PATCH',
    payload: { allocation },
    authenticated: true,
  })
}
