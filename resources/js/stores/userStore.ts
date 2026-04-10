import { defineStore } from 'pinia'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [] as any[],
    roles: [] as string[]
  }),

  actions: {
    setUsers(data: any[]) {
      this.users = data
    },

    setRoles(data: string[]) {
      this.roles = data
    }
  }
})
