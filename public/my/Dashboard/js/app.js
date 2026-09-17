// Previous values
let previous = {
  authority: 0,
  deans: 0,
  hods: 0,
  pcs: 0,
  faculty: 0,
  visiting: 0,
  lab: 0,
  staff: 0,
  students: 0,
};

// Animate Counter
function animateValue(id, start, end, duration = 1000) {
  if (start === end) return;

  const element = document.getElementById(id);

  // Get the fixed value after "/"
  const fixedValue = element.textContent.split("/")[1];

  const range = end - start;
  const startTime = performance.now();

  function update(currentTime) {
    const progress = Math.min((currentTime - startTime) / duration, 1);

    const value = Math.floor(start + range * progress);

    // Update only the number before "/"
    element.textContent = value.toLocaleString() + "/" + fixedValue;

    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

// Load Dashboard Data
async function loadDashboard() {
  try {
    const response = await fetch(
      "https://learningplex.gsfcuniversity.in/local/launchcounter/api.php?" +
        Date.now(),
    );

    const data = await response.json();

    animateValue("authority-count", previous.authority, data.authority);
    animateValue("deans-count", previous.deans, data.deans);
    animateValue("hods-count", previous.hods, data.hods);
    animateValue("pcs-count", previous.pcs, data.pcs);
    animateValue("faculty-count", previous.faculty, data.faculty);
    animateValue("visiting-count", previous.visiting, data.visiting);
    animateValue("lab-count", previous.lab, data.lab);
    animateValue("staff-count", previous.staff, data.staff);
    animateValue("students-count", previous.students, data.students);
    previous.authority = data.authority;
    previous.students = data.students;
    previous.faculty = data.faculty;
    previous.deans = data.deans;
    previous.hods = data.hods;
    previous.pcs = data.pcs;
    previous.visiting = data.visiting;
    previous.lab = data.lab;
    previous.staff = data.staff;
  } catch (error) {
    console.error("Unable to load dashboard data.", error);
  }
}
document.querySelectorAll(".card").forEach((card) => {
  card.addEventListener("click", function () {
    const group = this.dataset.group;

    if (group === "students") {
      window.location.href = "details2.html?group=" + group;
    } else {
      window.location.href = "details.html?group=" + group;
    }
  });
});
// Initial load
loadDashboard();

// Refresh every 5 seconds
setInterval(loadDashboard, 5000);
