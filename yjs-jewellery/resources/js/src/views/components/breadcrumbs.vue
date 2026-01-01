<template>
    <div class="breadcrumbFrame">
        <b-breadcrumb :items="breadcrumbItems" />
    </div>
</template>

<script>
export default {
  computed: {
    breadcrumbItems() {
      const pathArray = this.$route.path.split('/').filter(p => p)
      const items = [{ text: 'Home', to: '/' }]
      let path = ''

      pathArray.forEach((segment, index) => {
        path += `/${segment}`
        items.push({
          text: this.formatSegment(segment),
          to: index === pathArray.length - 1 ? null : path,
        })
      })
      return items
    }
  },
  methods: {
    formatSegment(segment) {
      return segment.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    }
  }
}
</script>
