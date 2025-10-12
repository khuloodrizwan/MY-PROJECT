document.addEventListener("DOMContentLoaded", function () {
    const courseSelect = document.querySelector('select[name="coursename"]');
    const totalFeesInput = document.getElementById("total_fees");
    const dueFeesInput = document.getElementById("due_fees");

    const feesMap = {
        "bba(ca)": 60000,
        "bca": 60000,
        "bcs": 65000,
        "bba": 55000,
        "mba": 90000,
        "ba": 50000,
        "bba(ib)": 58000,
        "mca": 80000,
        "mcs": 75000
    };

    courseSelect.addEventListener("change", function () {
        const selectedCourse = this.value;
        const fees = feesMap[selectedCourse] || 0;
        totalFeesInput.value = fees;
        dueFeesInput.value = fees;
    });
});
