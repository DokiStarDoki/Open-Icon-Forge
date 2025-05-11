const searchInput = document.getElementById("search");
const tagFilter = document.getElementById("tagFilter");
const pageSizeSelect = document.getElementById("pageSize");
const iconGrid = document.getElementById("iconGrid");
const prevPageBtn = document.getElementById("prevPage");
const nextPageBtn = document.getElementById("nextPage");
const pageInfo = document.getElementById("pageInfo");
const details = document.getElementById("details");
const detailIcon = document.getElementById("detailIcon");
const detailName = document.getElementById("detailName");
const detailTags = document.getElementById("detailTags");
const detailTheme = document.getElementById("detailTheme");
const detailDate = document.getElementById("detailDate");
const detailPath = document.getElementById("detailPath");
const downloadBtn = document.getElementById("downloadBtn");

let icons = [];
let filteredIcons = [];
let currentPage = 1;
let pageSize = 20;
let selectedDiv = null;

// Lazy loading with IntersectionObserver
const observer = new IntersectionObserver(
  (entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const iconDiv = entry.target;
        const object = iconDiv.querySelector("object");
        const placeholder = iconDiv.querySelector(".icon-placeholder");
        if (object && placeholder) {
          object.data = iconDiv.dataset.file;
          object.onload = () => {
            placeholder.style.display = "none";
          };
          object.onerror = () => {
            console.error(`Failed to load SVG at ${iconDiv.dataset.file}`);
          };
        }
        observer.unobserve(iconDiv);
      }
    });
  },
  { rootMargin: "200px", threshold: 0.1 }
);

async function loadIcons() {
  try {
    const res = await fetch("icons/items.json"); // Adjust path as needed
    const data = await res.json();
    icons = data.map((icon) => ({
      ...icon,
      file: `icons/${icon.name}.svg`,
    }));
    document.getElementById(
      "iconCount"
    ).textContent = `(${icons.length} icons)`;
    populateTagDropdown();
    filterAndRender();
  } catch (err) {
    console.error("Failed to load metadata:", err);
    iconGrid.innerHTML = "<p>Failed to load icons.</p>";
  }
}

function populateTagDropdown() {
  const tags = new Set();
  icons.forEach((icon) => icon.tags.forEach((tag) => tags.add(tag)));
  Array.from(tags)
    .sort()
    .forEach((tag) => {
      const option = document.createElement("option");
      option.value = tag;
      option.textContent = tag;
      tagFilter.appendChild(option);
    });
}

function filterAndRender() {
  const searchTerm = searchInput.value.toLowerCase();
  const selectedTag = tagFilter.value;

  filteredIcons = icons.filter((icon) => {
    const matchesSearch =
      icon.name.toLowerCase().includes(searchTerm) ||
      icon.short_description.toLowerCase().includes(searchTerm);
    const matchesTag = selectedTag ? icon.tags.includes(selectedTag) : true;
    return matchesSearch && matchesTag;
  });

  currentPage = 1;
  renderPage();
}

function renderPage() {
  iconGrid.innerHTML = "";
  const start = (currentPage - 1) * pageSize;
  const end =
    pageSize === "all"
      ? filteredIcons.length
      : Math.min(start + parseInt(pageSize), filteredIcons.length);
  const iconsToShow = filteredIcons.slice(start, end);

  iconsToShow.forEach((icon) => {
    const div = document.createElement("div");
    div.className = "icon";
    div.dataset.file = icon.file;

    const placeholder = document.createElement("div");
    placeholder.className = "icon-placeholder";
    placeholder.style.height = "70%";
    placeholder.style.background = "#f0f0f0";
    placeholder.style.margin = "0 auto";

    const object = document.createElement("object");
    object.type = "image/svg+xml";

    const label = document.createElement("small");
    label.textContent = icon.name;

    const download = document.createElement("button");
    download.className = "quick-download";
    download.innerText = "🡇";
    download.title = "Download SVG";
    download.addEventListener("click", (e) => {
      e.stopPropagation();
      downloadIcon(icon.file);
    });

    div.appendChild(placeholder);
    div.appendChild(object);
    div.appendChild(label);
    div.appendChild(download);
    div.addEventListener("click", () => selectIcon(div, icon));
    iconGrid.appendChild(div);
    observer.observe(div);
  });

  // GSAP animation for icons
  gsap.from(".icon", {
    opacity: 0,
    scale: 0.8,
    rotation: 10,
    duration: 0.5,
    stagger: 0.05,
    ease: "back.out(1.7)",
  });

  updatePagination();
}

function updatePagination() {
  const totalItems = filteredIcons.length;
  const totalPages = pageSize === "all" ? 1 : Math.ceil(totalItems / pageSize);

  prevPageBtn.disabled = currentPage === 1;
  nextPageBtn.disabled = currentPage === totalPages || totalItems === 0;

  pageInfo.textContent =
    totalItems === 0
      ? "No icons found"
      : `Page ${currentPage} of ${totalPages} (${totalItems} icons)`;
}

function selectIcon(div, icon) {
  if (selectedDiv) selectedDiv.classList.remove("selected");
  selectedDiv = div;
  div.classList.add("selected");

  detailIcon.data = icon.file;
  detailName.textContent = icon.name;
  detailTags.textContent = icon.tags.join(", ");
  detailTheme.textContent = icon.theme;
  detailDate.textContent = icon.date_created;
  detailPath.textContent = icon.file;

  // GSAP animation for details panel
  gsap.fromTo(
    details,
    { opacity: 0, y: 50, rotationX: 10, filter: "blur(5px)" },
    {
      display: "block",
      opacity: 1,
      y: 0,
      rotationX: 0,
      filter: "blur(0px)",
      duration: 0.6,
      ease: "power3.out",
      onStart: () => (details.style.display = "block"),
      onComplete: () => {
        gsap.from(".details-content > *", {
          opacity: 0,
          y: 20,
          duration: 0.4,
          stagger: 0.1,
          ease: "power2.out",
        });
      },
    }
  );

  downloadBtn.onclick = () => downloadIcon(icon.file);
}

function downloadIcon(file) {
  const link = document.createElement("a");
  link.href = file;
  link.download = file.split("/").pop();
  link.click();
}

// Debounce function
function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
}

// Event listeners
searchInput.addEventListener("input", debounce(filterAndRender, 300));
tagFilter.addEventListener("change", filterAndRender);
pageSizeSelect.addEventListener("change", () => {
  pageSize = pageSizeSelect.value;
  currentPage = 1;
  renderPage();
});
prevPageBtn.addEventListener("click", () => {
  if (currentPage > 1) {
    currentPage--;
    renderPage();
    gsap.fromTo(
      prevPageBtn,
      { scale: 1 },
      { scale: 0.9, duration: 0.2, ease: "power1.inOut", yoyo: true, repeat: 1 }
    );
  }
});
nextPageBtn.addEventListener("click", () => {
  const totalPages =
    pageSize === "all" ? 1 : Math.ceil(filteredIcons.length / pageSize);
  if (currentPage < totalPages) {
    currentPage++;
    renderPage();
    gsap.fromTo(
      nextPageBtn,
      { scale: 1 },
      { scale: 0.9, duration: 0.2, ease: "power1.inOut", yoyo: true, repeat: 1 }
    );
  }
});

// Initial load
loadIcons();
