<?php include("partials/head.php"); ?>
<?php include("partials/header.php"); ?>

<!-- Contact Hero Section -->
<section class="bg-cream py-12">
  <div class="max-w-4xl mx-auto px-4 text-center">
    <h1 class="text-3xl md:text-4xl font-bold text-deepgreen">Contact Us</h1>
    <p class="mt-3 text-darkgray">
      Agar aapko koi query ya custom order request hai, toh humse contact kijiye.
    </p>
  </div>
</section>

<!-- Contact Form & Info -->
<section class="py-12 bg-white">
  <div class="max-w-7xl mx-auto px-4 grid md:grid-cols-2 gap-10">
    
    <!-- Contact Form -->
    <div class="bg-cream p-6 rounded-lg shadow">
      <h2 class="text-xl font-bold text-gold mb-4">Send us a Message</h2>
      <form class="space-y-4">
        <input type="text" placeholder="Your Name" 
               class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gold">
        <input type="email" placeholder="Your Email" 
               class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gold">
        <input type="text" placeholder="Phone / WhatsApp" 
               class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gold">
        <textarea rows="4" placeholder="Your Message" 
                  class="w-full border border-gray-200 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gold"></textarea>
        <button type="submit" 
                class="bg-deepgreen text-white w-full py-3 rounded-lg hover:bg-gold hover:text-darkgray transition">
          Send Message
        </button>
      </form>
    </div>

    <!-- Contact Info -->
    <div class="p-6">
      <h2 class="text-xl font-bold text-gold mb-4">Get in Touch</h2>
      <p class="text-darkgray mb-4">
        Hum aapki seva me hamesha uplabdh hain. WhatsApp / Call details yahan add karein.
      </p>
      <ul class="space-y-3 text-darkgray">
        <li><strong>📍 Address:</strong> Nathdwara, Rajasthan, India</li>
        <li><strong>📞 Phone:</strong> +91-XXXXXXXXXX</li>
        <li><strong>💬 WhatsApp:</strong> +91-XXXXXXXXXX</li>
        <li><strong>✉️ Email:</strong> info@pushtimargshringar.com</li>
      </ul>

      <!-- Google Map -->
      <div class="mt-6">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3672.0717288933!2d73.8215!3d24.9386!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3967f4b3c17ff9df%3A0xaaa4a1b1b1e4a4b!2sNathdwara%2C%20Rajasthan!5e0!3m2!1sen!2sin!4v0000000000000" 
          width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy">
        </iframe>
      </div>
    </div>
  </div>
</section>

<?php include("partials/footer.php"); ?>
<?php include("partials/scripts.php"); ?>
