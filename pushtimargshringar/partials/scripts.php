<?php
// partials/scripts.php
?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    console.log("Main JS directly loaded");

    // ---------- Your existing slider code ----------
    const slider = document.getElementById("slider");
    let offset = 0;
    document.querySelectorAll(".slider-arrow").forEach(btn => {
      btn.addEventListener("click", () => {
        if (!slider) return;
        if (btn.classList.contains("left")) offset += 260;
        else offset -= 260;
        slider.style.transform = `translateX(${offset}px)`;
      });
    });

    // ---------- Toast helper ----------
    function showToast(msg, ok = true) {
      const t = document.createElement('div');
      t.textContent = msg;
      t.style.position = 'fixed';
      t.style.right = '20px';
      t.style.bottom = '20px';
      t.style.padding = '10px 14px';
      t.style.borderRadius = '8px';
      t.style.boxShadow = '0 6px 18px rgba(0,0,0,0.08)';
      t.style.zIndex = 9999;
      t.style.color = ok ? '#064e3b' : '#7f1d1d';
      t.style.background = ok ? '#bbf7d0' : '#fecaca';
      document.body.appendChild(t);
      setTimeout(() => {
        t.style.transition = 'opacity 300ms';
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 300);
      }, 1700);
    }

    // ---------- Cart counter update ----------
    function setCartCount(n) {
      const el = document.querySelector('.cart-count');
      if (el) { el.textContent = n; return; }
      const a = document.querySelector('a[href*="cart.php"]');
      if (a) {
        let badge = a.querySelector('.cart-count-inline');
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'cart-count-inline';
          badge.style.marginLeft = '8px';
          badge.style.background = '#f59e0b';
          badge.style.color = '#fff';
          badge.style.padding = '2px 8px';
          badge.style.borderRadius = '6px';
          badge.style.fontSize = '0.85em';
          a.appendChild(badge);
        }
        badge.textContent = n;
      }
    }

    // initialize from PHP session
    setCartCount(<?= json_encode(array_sum(array_map('intval', $_SESSION['cart'] ?? []))) ?>);

    // ---------- AJAX Add to cart ----------
    async function addToCart(slug, qty, btn) {
      if (!slug) return;
      try {
        btn && (btn.disabled = true);
        const fd = new FormData();
        fd.append('slug', slug);
        fd.append('qty', qty || 1);

        const resp = await fetch('<?= site_url("cart.php?action=add") ?>', {
          method: 'POST',
          headers: { 'Accept': 'application/json','X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          body: fd
        });

        const data = await resp.json();
        if (data && data.ok) {
          setCartCount(data.cart_count ?? 0);
          showToast('Item added to cart');
        } else {
          showToast('Could not add item', false);
        }
      } catch (err) {
        console.error(err);
        showToast('Error adding to cart', false);
      } finally {
        btn && (btn.disabled = false);
      }
    }

    document.addEventListener('click', function(e){
      const btn = e.target.closest && e.target.closest('.add-to-cart');
      if (!btn) return;
      e.preventDefault();
      const slug = btn.dataset.slug;
      const qty = btn.dataset.qty ? parseInt(btn.dataset.qty,10) : 1;
      addToCart(slug, qty, btn);
    });

    // ---------- Quantity increment (cart page) ----------
    document.addEventListener('click', function(e){
      if (e.target.classList.contains('cart-qty-dec') || e.target.classList.contains('cart-qty-inc')) {
        const id = e.target.getAttribute('data-target');
        const input = document.getElementById(id);
        if (!input) return;
        const val = Math.max(1, parseInt(input.value || '1', 10));
        input.value = e.target.classList.contains('cart-qty-inc') ? (val + 1) : Math.max(1, val - 1);
      }
    });
  });
</script>
</body>
</html>
