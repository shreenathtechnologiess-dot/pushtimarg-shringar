<?php include("partials/head.php"); ?>
<?php include("partials/header.php"); ?>

<!-- Blog Page -->
<section class="py-10 bg-cream">
  <div class="max-w-7xl mx-auto px-4">
    <!-- Page Title -->
    <div class="mb-10 text-center">
      <h1 class="text-3xl font-bold text-deepgreen">Our Blog</h1>
      <p class="text-gray-600 mt-2">Latest updates, festivals & shringar stories</p>
    </div>

    <!-- Blog Posts Grid -->
    <div class="grid md:grid-cols-2 gap-8">
      <!-- Blog Card -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
        <img src="assets/images/blog/blog1.jpg" alt="Blog 1" class="w-full h-56 object-cover">
        <div class="p-5">
          <span class="text-sm text-gold">21 Oct 2025</span>
          <h2 class="text-xl font-semibold text-darkgray mt-2">Akshay Trutiya Special</h2>
          <p class="text-gray-600 mt-2 line-clamp-3">
            Thakur ji ke vishesh shringar aur darshan Akshay Trutiya par…
          </p>
          <a href="blog-detail.php?id=1" class="inline-block mt-4 text-deepgreen font-medium hover:underline">
            Read More →
          </a>
        </div>
      </div>

      <!-- Blog Card -->
      <div class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden">
        <img src="assets/images/blog/blog2.jpg" alt="Blog 2" class="w-full h-56 object-cover">
        <div class="p-5">
          <span class="text-sm text-gold">21 Oct 2025</span>
          <h2 class="text-xl font-semibold text-darkgray mt-2">Sharad Purnima Special</h2>
          <p class="text-gray-600 mt-2 line-clamp-3">
            Shrinath ji ka anokha shringar aur utsav Sharad Purnima ke avsar par…
          </p>
          <a href="blog-detail.php?id=2" class="inline-block mt-4 text-deepgreen font-medium hover:underline">
            Read More →
          </a>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center mt-10 space-x-2">
      <a href="#" class="px-3 py-2 border rounded hover:bg-gold hover:text-white">Prev</a>
      <a href="#" class="px-3 py-2 border rounded bg-deepgreen text-white">1</a>
      <a href="#" class="px-3 py-2 border rounded hover:bg-gold hover:text-white">2</a>
      <a href="#" class="px-3 py-2 border rounded hover:bg-gold hover:text-white">Next</a>
    </div>
  </div>
</section>

<?php include("partials/footer.php"); ?>
<?php include("partials/scripts.php"); ?>
