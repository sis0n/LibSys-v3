document.addEventListener('DOMContentLoaded', function () {
  const totalUsersEl = document.getElementById('totalUsers');
  const dailyVisitorsEl = document.getElementById('dailyVisitors');
  const availableBooksEl = document.getElementById('activeBooks'); 
  const borrowedBooksEl = document.getElementById('borrowedBooks');
  const topVisitorsTableBody = document.getElementById('topVisitorsTableBody');
  const weeklyActivityCtx = document.getElementById('weeklyActivityChart')?.getContext('2d');

  let weeklyActivityChartInstance = null;

  async function loadDashboardData() {
    try {
      const response = await fetch(`api/superadmin/dashboard/getData`);
      const result = await response.json();

      if (result.success) {
        const data = result.data;

        if (totalUsersEl) totalUsersEl.textContent = data.totalUsers;
        if (dailyVisitorsEl) dailyVisitorsEl.textContent = data.attendance_today;
        if (availableBooksEl) availableBooksEl.textContent = data.availableBooks;
        if (borrowedBooksEl) borrowedBooksEl.textContent = data.borrowed_books;

        const totalUsersSub = document.querySelector("#totalUsers + p");
        if (totalUsersSub) totalUsersSub.textContent = `+${data.usersAddedThisMonth} this month`;

        const availableSub = document.querySelector("#activeBooks + p");
        if (availableSub) availableSub.textContent = `${data.availableBooksPercent}% available`;

        const borrowedSub = document.querySelector("#borrowedBooks + p");
        if (borrowedSub) borrowedSub.textContent = `${data.borrowedBooksPercent}% of total books`;

        renderTopVisitorsTable(result.topVisitors);
        renderWeeklyChart(result.weeklyActivity);
      }
    } catch (error) {
      console.error('Error loading dashboard:', error);
    }
  }

  function renderTopVisitorsTable(visitors) {
    if (!topVisitorsTableBody) return;

    if (!visitors || visitors.length === 0) {
      topVisitorsTableBody.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-gray-400 italic text-xs uppercase font-bold">No records found</td></tr>';
      return;
    }

    topVisitorsTableBody.innerHTML = visitors.map((v, index) => {
      return `
        <tr class="hover:bg-orange-50/30 transition-colors">
          <td class="px-4 py-3 text-left font-black text-orange-600">${index + 1}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-tight text-[11px]">${v.user_name || "Unknown User"}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[11px]">${v.student_number}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[11px]">${v.year_level} - ${v.section}</td>
          <td class="px-4 py-3 text-right font-black text-gray-800">${v.visits}</td>
        </tr>
      `;
    }).join('');
  }

  function renderWeeklyChart(activity) {
    if (!weeklyActivityCtx) return;

    const labels = activity.map(w => w.day);
    const visitorsData = activity.map(w => w.visitors);
    const borrowsData = activity.map(w => w.borrows);

    if (weeklyActivityChartInstance) weeklyActivityChartInstance.destroy();

    weeklyActivityChartInstance = new Chart(weeklyActivityCtx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [{
            label: "Visitors",
            data: visitorsData,
            borderColor: "#3b82f6",
            backgroundColor: "rgba(59,130,246,0.1)",
            tension: 0.4,
            fill: true
          },
          {
            label: "Checkouts",
            data: borrowsData,
            borderColor: "#f59e0b",
            backgroundColor: "rgba(245,158,11,0.1)",
            tension: 0.4,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }

  loadDashboardData();
});
