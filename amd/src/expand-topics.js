/**
 * Update course index to reflect current section
 * @param {string} sectionNumber - The section number to set as active
 */
const updateCourseIndexState = (sectionNumber) => {
  // Remove page-item class and set aria-selected="false" for all course index items
  const allCourseIndexItems = document.querySelectorAll(
    "#courseindex .courseindex-section > .courseindex-item.pageitem"
  );
  allCourseIndexItems.forEach((item) => {
    const parentSection = item.closest(".courseindex-section");
    const itemSectionNumber = parentSection.dataset.number;

    // Only update if this is not the target section
    if (itemSectionNumber !== sectionNumber) {
      item.classList.remove("pageitem");
      item.setAttribute("aria-selected", "false");
    }
  });

  // Set the target section as active
  const targetSection = document.querySelector(
    `#courseindex .courseindex-section[data-number="${sectionNumber}"]`
  );
  if (targetSection) {
    const targetItem = targetSection.querySelector(".courseindex-item");
    if (targetItem) {
      targetItem.classList.add("pageitem");
      targetItem.setAttribute("aria-selected", "true");
    }
  }
};

/**
 * Async version of scrollIntoView that resolves when scrolling is complete
 * @param {Element} element - The element to scroll to
 * @param {Object} options - ScrollIntoView options
 * @returns {Promise} - Resolves when scrolling is complete
 */
const scrollIntoViewAsync = (element, options = {}) => {
  return new Promise((resolve) => {
    // Get current scroll position before starting
    let currentPos = window.pageYOffset || document.documentElement.scrollTop;
    let lastPos = currentPos;
    let stableCount = 0;

    // Start the scroll
    element.scrollIntoView(options);

    // Monitor scroll position
    const checkScroll = () => {
      currentPos = window.pageYOffset || document.documentElement.scrollTop;

      // If position hasn't changed since last check
      if (Math.abs(currentPos - lastPos) < 1) {
        stableCount += 1;
        // Wait for 2 stable samples (200ms total) before resolving
        if (stableCount >= 2) {
          resolve();
          return;
        }
      } else {
        // Position changed, reset stable count
        stableCount = 0;
      }

      lastPos = currentPos;
      setTimeout(checkScroll, 100);
    };

    // Start monitoring after a small delay
    setTimeout(checkScroll, 100);
  });
};

const handleButtonClick = function (event) {
  event.preventDefault();

  const button = event.target.closest(".courseindex-section");

  if (button) {
    const url = button.href;
    let sectionNumber = null;

    // First check if it's already a hashbang URL
    const hashMatch = url.match(/#section-(\d+)/);
    if (hashMatch) {
      sectionNumber = hashMatch[1];
    } else {
      // Check if it's a course/section.php URL and extract section number from data attribute
      const sectionMatch = url.match(/course\/section\.php\?id=(\d+)/);
      if (sectionMatch) {
        sectionNumber = button.dataset.number;
      }
    }

    // If we have a section number, handle it as an internal navigation
    if (sectionNumber) {
      const courseSection = document.getElementById(`section-${sectionNumber}`);
      if (courseSection) {
        // Expand the relevant section if needed.
        const sectionId = button.dataset.id;
        const courseContentCollapse = document.getElementById(
          `coursecontentcollapseid${sectionId}`
        );
        const collapseSection = document.getElementById(
          `collapsesectionid${sectionId}`
        );

        if (
          courseContentCollapse &&
          !courseContentCollapse.classList.contains("show")
        ) {
          courseContentCollapse.classList.add("show");
        }

        if (
          collapseSection &&
          collapseSection.classList.contains("collapsed")
        ) {
          collapseSection.classList.remove("collapsed");
          collapseSection.setAttribute("aria-expanded", "true");
        }

        // Go to section.

        // Use async scroll and update URL when complete
        scrollIntoViewAsync(courseSection, { behavior: "smooth" }).then(() => {
          window.history.replaceState(null, null, `#section-${sectionNumber}`);
          updateCourseIndexState(sectionNumber);
        });

        return;
      }
    }

    // Fallback: Go to the external section.
    window.location.href = url;
  }
};

const courseIndexEvent = () => {
  const courseIndex = document.getElementById("courseindex-content");

  if (courseIndex) {
    courseIndex.addEventListener("click", handleButtonClick);
  }
};

// Only want to execute on topics view.
const expandTopics = () => {
  if (
    document.getElementById("page-course-view-topics") &&
    !document.querySelectorAll(".section-summary").length > 0
  ) {
    courseIndexEvent();
  }
};

export const init = () => {
  expandTopics();
};
