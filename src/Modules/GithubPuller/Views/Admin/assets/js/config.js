(() => {
  const secretKeyEl = document.querySelector('#secretKey');
  const randomizeEl = secretKeyEl.parentElement.querySelector('button');

  secretKeyEl.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey || e.altKey) {
      return;
    }
    secretKeyEl.setAttribute('type', 'password');
  });

  randomizeEl.addEventListener('click', () => {
    const arr = new Uint8Array(8);
    window.crypto.getRandomValues(arr);
    secretKeyEl.setAttribute('type', 'text');
    secretKeyEl.value = btoa(String(arr)).replaceAll('=', '');
  });
}) ();
