// stores/counter.js
import { defineStore } from 'pinia'

export const usePromotionStore = defineStore('promotion', {
  state: () => {
    return { list: [] }
  },
  // could also be defined as
  // state: () => ({ count: 0 })
  actions: {
    set(list) {
      this.list = list;
    },
    clear() {
      this.list = [];
    },
    add(number){
      this.list.push(number)
    }
  },
})