<template>
  <div class="page-print-decoration" aria-hidden="true">
    <img class="page-print-one" src="/print.png" alt="" />
    <img class="page-print-two" src="/print.png" alt="" />
  </div>
</template>

<style scoped>
.page-print-decoration {
  position: fixed;
  bottom: 0;
  left: -18px;
  z-index: -1;
  width: 145px;
  height: 170px;
  overflow: hidden;
  pointer-events: none;
}

.page-print-decoration img {
  position: absolute;
  height: auto;
}

.page-print-one {
  top: 8px;
  left: -56px;
  width: 126px;
}

.page-print-two {
  top: 55px;
  left: -14px;
  width: 132px;
}

@media (max-width: 760px) {
  .page-print-decoration {
    bottom: 60px;
    width: 138px;
    height: 158px;
  }

  .page-print-one {
    width: 116px;
  }

  .page-print-two {
    top: 49px;
    width: 122px;
  }
}
</style>
