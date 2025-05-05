const swiperWrapper = document.querySelector(".swiper-wrapper");
const searchInput = document.getElementById("search");
const tagFilter = document.getElementById("tagFilter");
const details = document.getElementById("details");
const detailIcon = document.getElementById("detailIcon");
const detailName = document.getElementById("detailName");
const detailTags = document.getElementById("detailTags");
const detailTheme = document.getElementById("detailTheme");
const detailDate = document.getElementById("detailDate");
const detailPath = document.getElementById("detailPath");
const downloadBtn = document.getElementById("downloadBtn");

let icons = [];
let selectedDiv = null;
let swiper = null;
const iconsPerPanel = 20;

async function loadIcons() {
  try {
    const res = await fetch("php/list_svgs.php");
    const data = await res.json();
    icons = data.icons || [];
  } catch (err) {
    console.warn("PHP failed, trying fallback metadata.json");
    try {
      const fallbackRes = await fetch("json/metadata.json");
      const fallbackData = await fallbackRes.json();
      icons = fallbackData.icons || [];
    } catch (fallbackErr) {
      swiperWrapper.innerHTML =
        "<p>Failed to load icons from both sources.</p>";
      console.error("Fallback also failed:", fallbackErr);
      return;
    }
  }

  const bannedThemes = ["vectorized", "temp", "generated"];
  const count = icons.filter(
    (icon) => !bannedThemes.includes(icon.theme?.toLowerCase())
  ).length;
  document.getElementById("iconCount").textContent = `(${count} icons)`;

  populateTagDropdown();
  renderCarousel();
}

function populateTagDropdown() {
  const tagCounts = {};
  const themeCounts = {};

  icons.forEach((icon) => {
    const theme = icon.theme;
    if (
      theme &&
      !["vectorized", "temp", "generated"].includes(theme.toLowerCase())
    ) {
      themeCounts[theme] = (themeCounts[theme] || 0) + 1;
    }

    (icon.tags || []).forEach((tag) => {
      tagCounts[tag] = (tagCounts[tag] || 0) + 1;
    });
  });

  tagFilter.innerHTML = '<option value="">Filter by tag or theme</option>';

  Object.keys(themeCounts)
    .sort()
    .forEach((theme) => {
      const option = document.createElement("option");
      option.value = `theme:${theme}`;
      option.textContent = `Theme: ${theme} (${themeCounts[theme]})`;
      tagFilter.appendChild(option);
    });

  Object.keys(tagCounts)
    .sort()
    .forEach((tag) => {
      const option = document.createElement("option");
      option.value = `tag:${tag}`;
      option.textContent = `Tag: ${tag} (${tagCounts[tag]})`;
      tagFilter.appendChild(option);
    });
}

function addHoverEffects(div) {
  div.addEventListener("mouseenter", () => {
    gsap.to(div, { scale: 1.08, duration: 0.2, ease: "power2.out" });
  });
  div.addEventListener("mouseleave", () => {
    gsap.to(div, { scale: 1, duration: 0.2, ease: "power2.out" });
  });
}

// Initialize Intersection Observer for panel lazy loading
const observerOptions = {
  root: null,
  rootMargin: "200px",
  threshold: 0,
};

const observer = new IntersectionObserver((entries, observer) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      const slide = entry.target;
      const objects = slide.querySelectorAll("object");
      const placeholders = slide.querySelectorAll(".icon-placeholder");

      objects.forEach((object, index) => {
        const filePath = object.parentElement.dataset.file;
        if (filePath && placeholders[index]) {
          object.data = filePath;
          placeholders[index].style.display = "none";
        }
      });

      observer.unobserve(slide);
    }
  });
}, observerOptions);

function chunkArray(array, size) {
  const chunks = [];
  for (let i = 0; i < array.length; i += size) {
    chunks.push(array.slice(i, i + size));
  }
  return chunks;
}

function renderCarousel(filter = "", selectedFilter = "") {
  const lowerFilter = filter.toLowerCase();
  const bannedThemes = ["vectorized", "temp", "generated"];
  let filterType = "";
  let filterValue = "";

  if (selectedFilter.includes(":")) {
    [filterType, filterValue] = selectedFilter.split(":");
  }

  const filteredIcons = icons.filter((icon) => {
    if (bannedThemes.includes(icon.theme?.toLowerCase())) return false;
    const matchesSearch =
      icon.name.toLowerCase().includes(lowerFilter) ||
      icon.theme.toLowerCase().includes(lowerFilter);
    const matchesTag =
      filterType === "tag" ? icon.tags?.includes(filterValue) : true;
    const matchesTheme =
      filterType === "theme" ? icon.theme === filterValue : true;
    return matchesSearch && matchesTag && matchesTheme;
  });

  const iconChunks = chunkArray(filteredIcons, iconsPerPanel);
  swiperWrapper.innerHTML = "";

  iconChunks.forEach((chunk, chunkIndex) => {
    const slide = document.createElement("div");
    slide.className = "swiper-slide";

    chunk.forEach((icon) => {
      const div = document.createElement("div");
      div.className = "icon";
      div.dataset.file = icon.file;

      const object = document.createElement("object");
      object.type = "image/svg+xml";
      object.style.display = "block";

      const placeholder = document.createElement("div");
      placeholder.className = "icon-placeholder";

      const label = document.createElement("small");
      label.textContent = icon.name;

      const download = document.createElement("button");
      download.className = "quick-download";
      download.innerText = "↓";
      download.title = "Download SVG";

      download.addEventListener("click", (e) => {
        e.stopPropagation();
        const link = document.createElement("a");
        link.href = icon.file;
        link.download = icon.file.split("/").pop();
        link.click();
      });

      div.appendChild(placeholder);
      div.appendChild(object);
      div.appendChild(label);
      div.appendChild(download);

      div.addEventListener("click", () => selectIcon(div, icon));
      addHoverEffects(div);
      slide.appendChild(div);
    });

    swiperWrapper.appendChild(slide);
    observer.observe(slide);
  });

  // Initialize or update Swiper
  if (swiper) {
    swiper.destroy(true, true);
  }

  swiper = new Swiper("#iconCarousel", {
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
    on: {
      slideChange: () => {
        // Ensure nearby slides are observed
        const activeIndex = swiper.activeIndex;
        const slides = document.querySelectorAll(".swiper-slide");
        slides.forEach((slide, index) => {
          if (
            Math.abs(index - activeIndex) <= 1 &&
            !slide.classList.contains("observed")
          ) {
            observer.observe(slide);
            slide.classList.add("observed");
          }
        });
      },
    },
  });

  gsap.from(".swiper-slide", {
    duration: 0.5,
    opacity: 0,
    scale: 0.9,
    stagger: 0.1,
    ease: "back.out(1.7)",
  });
}

function selectIcon(div, icon) {
  if (selectedDiv) selectedDiv.classList.remove("selected");
  selectedDiv = div;
  div.classList.add("selected");

  detailIcon.data = icon.file;
  detailName.textContent = icon.name;
  detailTags.textContent = icon.tags?.join(", ") || "";
  detailTheme.textContent = icon.theme || "";
  detailDate.textContent = icon.date_created || "";
  detailPath.textContent = icon.file;

  gsap.set(".details-content > *", {
    opacity: 0,
    y: 20,
  });

  gsap.fromTo(
    details,
    {
      display: "none",
      opacity: 0,
      y: 50,
      filter: "blur(8px)",
    },
    {
      display: "block",
      opacity: 1,
      y: 0,
      filter: "blur(0px)",
      duration: 0.15,
      ease: "expo.out",
      onStart: () => {
        details.style.display = "block";
      },
      onComplete: () => {
        gsap.to(".details-content > *", {
          opacity: 1,
          y: 0,
          duration: 0.25,
          stagger: 0.1,
          ease: "power3.out",
        });
      },
    }
  );

  downloadBtn.onclick = () => {
    const link = document.createElement("a");
    link.href = icon.file;
    link.download = icon.file.split("/").pop();
    link.click();
  };
}

// Debounce function
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Debounced input handlers
const debouncedRender = debounce((searchValue, filterValue) => {
  renderCarousel(searchValue, filterValue);
  gsap.to(details, {
    opacity: 0,
    duration: 0.2,
    onComplete: () => (details.style.display = "none"),
  });
  selectedDiv = null;
}, 300);

searchInput.addEventListener("input", (e) => {
  debouncedRender(e.target.value, tagFilter.value);
});

tagFilter.addEventListener("change", () => {
  debouncedRender(searchInput.value, tagFilter.value);
});

gsap.from("#mainTitle", {
  y: -30,
  opacity: 0,
  duration: 1,
  ease: "bounce.out",
});

loadIcons();
