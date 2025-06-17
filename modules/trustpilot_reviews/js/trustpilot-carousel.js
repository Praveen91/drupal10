(function (Drupal, once) {
  "use strict";

  /**
   * Initialize Trustpilot Reviews Carousel.
   */
  Drupal.behaviors.trustpilotCarousel = {
    attach: function (context, settings) {
      once(
        "trustpilot-carousel",
        ".trustpilot-reviews-carousel",
        context
      ).forEach(function (element) {
        const autoplay = element.getAttribute("data-autoplay") === "true";
        const delay = parseInt(element.getAttribute("data-delay")) || 5000;

        // Initialize Swiper
        const swiper = new Swiper(element, {
          slidesPerView: 1,
          spaceBetween: 30,
          loop: true,
          autoplay: autoplay
            ? {
                delay: delay,
                disableOnInteraction: false,
              }
            : false,
          pagination: {
            el: ".swiper-pagination",
            clickable: true,
          },
          navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
          },
          breakpoints: {
            640: {
              slidesPerView: 1,
              spaceBetween: 20,
            },
            768: {
              slidesPerView: 2,
              spaceBetween: 30,
            },
            1024: {
              slidesPerView: 3,
              spaceBetween: 30,
            },
          },
          // Lazy loading
          lazy: {
            loadPrevNext: true,
          },
          // Accessibility
          a11y: {
            prevSlideMessage: Drupal.t("Previous slide"),
            nextSlideMessage: Drupal.t("Next slide"),
            firstSlideMessage: Drupal.t("This is the first slide"),
            lastSlideMessage: Drupal.t("This is the last slide"),
          },
          // Keyboard control
          keyboard: {
            enabled: true,
            onlyInViewport: false,
          },
          // Mouse wheel control
          mousewheel: {
            invert: false,
          },
        });

        // Pause autoplay on hover
        if (autoplay) {
          element.addEventListener("mouseenter", function () {
            swiper.autoplay.stop();
          });

          element.addEventListener("mouseleave", function () {
            swiper.autoplay.start();
          });
        }

        // Handle visibility change (pause when tab is not active)
        document.addEventListener("visibilitychange", function () {
          if (autoplay) {
            if (document.hidden) {
              swiper.autoplay.stop();
            } else {
              swiper.autoplay.start();
            }
          }
        });

        // Store swiper instance for potential external access
        element.swiper = swiper;
      });
    },
  };
})(Drupal, once);
