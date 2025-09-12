<script>
(function(){
  const track = document.getElementById('slider');
  if (!track) return;
  const slides = track.querySelectorAll('img');
  const prev = document.getElementById('prev');
  const next = document.getElementById('next');
  let i = 0;

  function update() {
    const w = track.clientWidth;
    track.style.transform = 'translateX(' + (-i * w) + 'px)';
  }
  function go(n){ i = (n + slides.length) % slides.length; update(); }

  prev && prev.addEventListener('click', () => go(i-1));
  next && next.addEventListener('click', () => go(i+1));
  window.addEventListener('resize', update);
  setTimeout(update, 0);
  setInterval(()=>go(i+1), 5000);
})();
</script>
