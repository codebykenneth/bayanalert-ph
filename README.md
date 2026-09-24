# 🇵🇭 BayanAlert PH
### *Alerto sa Bayan. Ligtas ang Lahat.*
*(An Alert for the Nation. Safety for Everyone.)*

> **A Community Safety, Disaster Preparedness & Emergency Reporting Platform — Capstone Prototype**

---

## 📄 Abstract

The Philippines is one of the most disaster-prone countries in the world, sitting at the crossroads of the **Pacific Typhoon Belt** and the **Pacific Ring of Fire**. Every year, communities face floods, typhoons, earthquakes, and volcanic activity — often with information that is delayed, fragmented, or unverified.

**BayanAlert PH** is proposed as a unifying digital platform that connects **citizens**, **local government units (LGUs)**, and **emergency responders** on a single, transparent system for reporting incidents, issuing verified alerts, and locating critical safety infrastructure such as evacuation centers, hospitals, and police/fire stations.

This document presents the **rationale, objectives, and scope** of the system — the "why" behind the build — as a companion to the project's technical documentation.

---

## 🌊 1. Background & Context

| Hazard Type | Frequency in PH Context |
|---|---|
| 🌀 Typhoons | ~20 per year enter the Philippine Area of Responsibility |
| 🌋 Volcanic Activity | Multiple active volcanoes under continuous PHIVOLCS monitoring |
| 🌍 Earthquakes | Located along active fault lines and the Ring of Fire |
| 💧 Flooding | Seasonal and recurring in low-lying and urban barangays |

Despite this level of exposure, **emergency information in many communities still travels informally** — through group chats, word of mouth, or scattered social media posts — with no shared source of truth between residents and the officials responsible for helping them.

---

## ❗ 2. Statement of the Problem

During an emergency, three questions repeatedly go unanswered fast enough:

1. 🧑‍🤝‍🧑 **"What is actually happening, right now, near me?"**
   *(Citizens lack a single trusted channel to report or receive real-time updates.)*

2. 🗺️ **"Where do I go, and is it safe / open / full?"**
   *(Evacuation centers and facilities are not consistently mapped or status-tracked.)*

3. 🏛️ **"How do responders separate real reports from noise?"**
   *(LGUs and responders often work from unverified, unstructured public posts.)*

BayanAlert PH is designed as a direct response to these three gaps.

---

## 🎯 3. Objectives of the System

**General Objective**
> To design and prototype a role-based digital platform that improves the flow of verified safety information between citizens and local authorities in the Philippines.

**Specific Objectives**
- ✅ Enable citizens to **submit incident reports and SOS alerts** with location data
- ✅ Allow **admins to verify** community reports before they are treated as official
- ✅ Provide a **live map** of evacuation centers, hospitals, and police/fire stations
- ✅ Give **responders** the tools to triage, act on, and update the status of reports
- ✅ Deliver **preparedness education** ("Learn & Prepare") before disaster strikes
- ✅ Demonstrate secure, role-based system design suitable for public-sector use

---

## 👥 4. Stakeholder Overview

| Role | Core Need | What the System Gives Them |
|---|---|---|
| 🧑 **Citizen** | *"Keep me safe and informed"* | Reporting, SOS, alerts, evacuation map, prep guides |
| 🚒 **Responder (LGU)** | *"Let me act on what's real"* | Assigned reports, status updates, announcements |
| 🛡️ **Admin** | *"Keep the information trustworthy"* | Verification tools, alert management, full dashboard |

Access is enforced **server-side by role**, so each user only ever sees what's relevant and appropriate to their responsibility — a citizen cannot access admin tools, and vice versa.

---

## 🌱 5. Significance of the Study

- **To Citizens:** A single, calmer source of truth instead of scattered, unverified posts during high-stress moments.
- **To LGUs and Responders:** A structured intake system that reduces the chaos of manually monitoring social media for emergencies.
- **To the Academic Community:** A demonstration of applying secure, role-based web architecture (PHP, MySQL, PDO, CSRF protection, RBAC) to a real, socially meaningful problem.
- **To Future Developers:** An extensible foundation — not a finished product — built to be honestly upgraded rather than falsely presented as complete.

---

## 🧭 6. Scope and Limitations

**In Scope**
- ✔️ Citizen incident/SOS reporting with map integration (Leaflet.js + OpenStreetMap)
- ✔️ Verified alert publishing by admins
- ✔️ Facility and evacuation center directory
- ✔️ Role-based dashboards for Citizen / Responder / Admin
- ✔️ Preparedness education content

**Out of Scope (by design, for now)**
- ❌ Automatic dispatch of real emergency services — *always defer to 911*
- ❌ Live government weather data integration (currently links out to PAGASA)
- ❌ SMS/push notification delivery *(planned extension, not yet built)*

> 🔍 **A note on honesty in design:** Every piece of sample or demo data in this prototype is explicitly labeled as such. In a domain where lives could depend on the accuracy of information, a system that blurs the line between "real" and "placeholder" data is a design failure — even in a student project.

---

## 🚀 7. Future Direction

| Planned Enhancement | Purpose |
|---|---|
| 📡 Live PAGASA data feed | Replace placeholder weather with real bulletins |
| 📱 SMS/Push notifications | Reach citizens with limited or no internet access |
| 🗺️ DENR-MGB / PHIVOLCS hazard overlays | Add real hazard-mapping layers to the existing map |
| 🌐 Filipino/English toggle | Ensure alerts reach people in the language they understand best |

---

## 🏁 8. Conclusion

BayanAlert PH is not proposed as a finished emergency system, but as a **working demonstration** of how thoughtful, secure, and honest software design can meaningfully narrow the information gap between Filipino communities and the people responsible for keeping them safe.

At its core, the project rests on one idea:

> 🛡️ *In an emergency, the fastest help is the help that reaches the right person, verified, in time.*

---

*Ligtas ang lahat kapag alerto ang bayan.*
*— Everyone is safe when the nation is alert.*