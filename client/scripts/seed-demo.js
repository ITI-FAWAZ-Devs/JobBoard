// Demo Data Seed Script
// Run: node scripts/seed-demo.js
// Generates fake data via the API using an admin token.

const axios = require("axios");

const API = "http://127.0.0.1:8000/api/v1";
const ADMIN_TOKEN = process.env.ADMIN_TOKEN || "YOUR_ADMIN_TOKEN_HERE";

const api = axios.create({
  baseURL: API,
  headers: {
    "Content-Type": "application/json",
    Authorization: `Bearer ${ADMIN_TOKEN}`,
  },
});

const employers = [
  { name: "Acme Corp", email: "employer1@demo.com", password: "password" },
  { name: "Globex Inc", email: "employer2@demo.com", password: "password" },
  { name: "Initech", email: "employer3@demo.com", password: "password" },
];

const candidates = [
  { name: "Alice Johnson", email: "candidate1@demo.com", password: "password" },
  { name: "Bob Smith", email: "candidate2@demo.com", password: "password" },
  { name: "Carol Williams", email: "candidate3@demo.com", password: "password" },
];

const jobs = [
  { title: "Senior Vue Developer", description: "Build UIs with Vue 3.", status: "approved" },
  { title: "Laravel Backend Engineer", description: "Design REST APIs.", status: "approved" },
  { title: "Full Stack Developer", description: "Work across the stack.", status: "pending" },
  { title: "DevOps Engineer", description: "Manage CI/CD pipelines.", status: "pending" },
  { title: "UI/UX Designer", description: "Design beautiful interfaces.", status: "approved" },
];

async function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

async function run() {
  if (ADMIN_TOKEN === "YOUR_ADMIN_TOKEN_HERE") {
    console.error("❌ Set ADMIN_TOKEN env var to a valid admin bearer token.");
    process.exit(1);
  }

  console.log("🌱 Starting demo data seeding...\n");

  const employerIds = [];
  for (const emp of employers) {
    try {
      const { data } = await api.post("/register", {
        ...emp,
        role: "employer",
        password_confirmation: emp.password,
      });
      employerIds.push(data.user?.id || data.data?.id);
      console.log(`✅ Employer created: ${emp.email}`);
    } catch (e) {
      console.warn(`⚠️  Employer ${emp.email}: ${e.response?.data?.message || e.message}`);
    }
    await sleep(300);
  }

  const candidateIds = [];
  for (const can of candidates) {
    try {
      const { data } = await api.post("/register", {
        ...can,
        role: "candidate",
        password_confirmation: can.password,
      });
      candidateIds.push(data.user?.id || data.data?.id);
      console.log(`✅ Candidate created: ${can.email}`);
    } catch (e) {
      console.warn(`⚠️  Candidate ${can.email}: ${e.response?.data?.message || e.message}`);
    }
    await sleep(300);
  }

  const employerId = employerIds[0];
  if (!employerId) {
    console.error("❌ No employer was created. Aborting job creation.");
    process.exit(1);
  }

  const jobIds = [];
  for (const job of jobs) {
    try {
      const { data } = await api.post("/jobs", {
        ...job,
        employer_id: employerId,
      });
      const jobId = data.job?.id || data.data?.id;
      jobIds.push(jobId);
      console.log(`✅ Job created: "${job.title}" (${job.status})`);
    } catch (e) {
      console.warn(`⚠️  Job "${job.title}": ${e.response?.data?.message || e.message}`);
    }
    await sleep(300);
  }

  for (let i = 0; i < 3 && i < jobIds.length && i < candidateIds.length; i++) {
    try {
      const { data } = await api.post("/applications", {
        job_id: jobIds[i],
        candidate_id: candidateIds[i],
        cover_letter: `I am very interested in this position.`,
      });
      console.log(`✅ Application created: candidate ${candidateIds[i]} → job ${jobIds[i]}`);
    } catch (e) {
      console.warn(`⚠️  Application ${i + 1}: ${e.response?.data?.message || e.message}`);
    }
    await sleep(300);
  }

  console.log("\n🎉 Demo data seeding complete!");
  console.log("   Employers: employer1@demo.com, employer2@demo.com, employer3@demo.com");
  console.log("   Candidates: candidate1@demo.com, candidate2@demo.com, candidate3@demo.com");
  console.log("   Password for all: password");
}

run().catch(console.error);
