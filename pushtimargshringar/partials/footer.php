<?php
// footer.php

$current = basename($_SERVER['PHP_SELF']);
?>

<?php if ($current === 'index.php' || $current === 'products.php'): ?>
    <!-- Full Footer -->
    <footer class="bg-[#FFF6E5] mt-12 relative">
      <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-4 gap-8 text-sm">

        <!-- Left brand -->
        <div>
          <h4 class="font-semibold text-[#8B0000]">Pushtimarg Shringar</h4>
          <p class="mt-2 text-gray-600">Premium vastra & shringar for Thakur ji.</p>
        </div>

        <!-- Useful Links -->
        <div>
          <h4 class="font-semibold">Useful Links</h4>
          <ul class="mt-2 space-y-1">
            <li><a href="<?= SITE_URL ?>/about.php" class="hover:underline">About</a></li>
            <li><a href="<?= SITE_URL ?>/contact.php" class="hover:underline">Contact</a></li>
            <li><a href="<?= SITE_URL ?>/privacy-policy.php" class="hover:underline">Privacy Policy</a></li>
            <li><a href="<?= SITE_URL ?>/terms.php" class="hover:underline">Terms & Conditions</a></li>
            <li><a href="<?= SITE_URL ?>/cancellation.php" class="hover:underline">Cancellation Policy</a></li>
          </ul>
        </div>

        <!-- Address -->
        <div>
          <h4 class="font-semibold">Address</h4>
          <p class="mt-2 text-gray-600">Nathdwara, Rajsamand, Rajasthan</p>
          <p class="mt-1 text-gray-600">📞 +91 9876543210</p>
          <p class="mt-1 text-gray-600">✉️ info@pushtimargshringar.com</p>
        </div>

        <!-- Follow -->
        <div>
          <h4 class="font-semibold">Follow</h4>
          <ul class="mt-2 flex space-x-3">
            <li>
              <a href="https://instagram.com/yourusername" target="_blank">
                <img src="assets/images/icons/instagram.png" alt="Instagram" class="w-6 h-6">
              </a>
            </li>
            <li>
              <a href="https://wa.me/919999999999" target="_blank">
                <img src="assets/images/icons/whatsapp.png" alt="WhatsApp" class="w-6 h-6">
              </a>
            </li>
            <li>
              <a href="https://facebook.com/yourusername" target="_blank">
                <img src="assets/images/icons/facebook.png" alt="Facebook" class="w-6 h-6">
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="text-center py-4 text-xs text-gray-500 border-t">
        © <?= date('Y') ?> Pushtimarg Shringar. All rights reserved. |
        Designed by :- <span class="text-[#8B0000] font-medium hover:underline">Shreenath Technologies</span>
      </div>

      <!-- Floating WhatsApp Button -->
      <a href="https://wa.me/919999999999" target="_blank"  
         class="fixed bottom-6 right-6 w-14 h-14 rounded-full shadow-lg overflow-hidden">
         <img src="<?= SITE_URL ?>/assets/images/icons/whatsapp.png" alt="WhatsApp" class="w-full h-full object-cover">
      </a>
    </footer>

<?php else: ?>
    <!-- Simple Footer -->
    <footer class="bg-[#FFF6E5] mt-12 relative">
      <div class="text-center py-4 text-sm text-gray-600 border-t">
        © <?= date('Y') ?> Pushtimarg Shringar. All rights reserved. |
        Designed by :- <span class="text-[#8B0000] font-medium hover:underline">Shreenath Technologies</span>
      </div>

      <!-- Floating WhatsApp Button -->
      <a href="https://wa.me/919999999999" target="_blank"  
         class="fixed bottom-6 right-6 w-14 h-14 rounded-full shadow-lg overflow-hidden">
         <img src="<?= SITE_URL ?>/assets/images/icons/whatsapp.png" alt="WhatsApp" class="w-full h-full object-cover">
      </a>
    </footer>
<?php endif; ?>
