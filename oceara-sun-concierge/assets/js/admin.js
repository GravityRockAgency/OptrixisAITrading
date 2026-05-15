(() => {
  document.querySelectorAll('.osc-admin details').forEach((details) => {
    details.addEventListener('toggle', () => {
      if (!details.open) return;
      document.querySelectorAll('.osc-admin details').forEach((other) => {
        if (other !== details) other.open = false;
      });
    });
  });
})();
