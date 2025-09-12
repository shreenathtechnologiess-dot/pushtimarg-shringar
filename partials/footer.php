<footer class="bg-cream border-t border-gray-200 mt-10">

  <?php
    // current page ka naam nikalna
    $currentPage = basename($_SERVER['PHP_SELF']);

    // sirf index.php aur products.php par show kare
    if ($currentPage === 'index.php' || $currentPage === 'products.php'):
  ?>
    <!-- Main Footer Content -->
    <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 md:grid-cols-4 gap-8">

      <!-- Information -->
      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">Information</h3>
        <ul class="space-y-2 text-darkgray">
          <li><a href="about.php" class="hover:text-gold">About Us</a></li>
          <li><a href="privacy-policy.php" class="hover:text-gold">Privacy Policy</a></li>
          <li><a href="terms.php" class="hover:text-gold">Terms & Conditions</a></li>
          <li><a href="cancellation.php" class="hover:text-gold">Cancellation Policy</a></li>
        </ul>
      </div>

      <!-- My Account -->
      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">My Account</h3>
        <ul class="space-y-2 text-darkgray">
          <li><a href="account/index.php" class="hover:text-gold">My Account</a></li>
          <li><a href="account/orders.php" class="hover:text-gold">Order History</a></li>
          <li><a href="wishlist.php" class="hover:text-gold">Wishlist</a></li>
          <li><a href="newsletter.php" class="hover:text-gold">Newsletter</a></li>
        </ul>
      </div>

      <!-- Customer Service -->
      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">Customer Service</h3>
        <ul class="space-y-2 text-darkgray">
          <li><a href="contact.php" class="hover:text-gold">Contact</a></li>
          <li><a href="returns.php" class="hover:text-gold">Returns</a></li>
          <li><a href="delivery.php" class="hover:text-gold">Delivery</a></li>
          <li><a href="sitemap.php" class="hover:text-gold">Site Map</a></li>
        </ul>
      </div>

      <!-- Newsletter -->
      <div>
        <h3 class="text-lg font-bold text-deepgreen mb-4">Newsletter</h3>
        <p class="text-darkgray mb-4">Don’t miss updates or promotions, sign up for our newsletter.</p>
        <form action="subscribe.php" method="post" class="flex">
          <input type="email" name="email" placeholder="Your email" 
                class="w-full border border-gray-300 p-2 rounded-l focus:outline-none">
          <button type="submit" class="bg-deepgreen text-white px-4 rounded-r hover:bg-gold hover:text-darkgray">
            SEND
          </button>
        </form>
      </div>

    </div>
  <?php endif; ?>

  <!-- Bottom -->
  <div class="bg-deepgreen text-white py-4 text-center text-sm">
    <p>
      &copy; 2025 Pushtimarg Shringar | All Rights Reserved  
      | Designed by: <span class="font-semibold text-gold">Shreenath Technologies</span>
    </p>
  </div>

  <!-- Floating WhatsApp Button -->
  <a href="https://wa.me/919999999999" target="_blank"  
     class="fixed bottom-6 right-6 bg-green-500 p-4 rounded-full shadow-lg text-white text-2xl hover:bg-green-600 transition">
     <i class="fab fa-whatsapp"></i>
  </a>
</footer>
