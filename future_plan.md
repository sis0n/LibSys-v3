# LibSys v3.0 - Plano para sa Multi-Campus System (Overview)

Ang dokumentong ito ay ginawa para sa mga **Campus Heads** at **Librarians** upang ipaliwanag ang mga bagong kakayahan ng ating Library Information System (LibSys) at ang mga susunod na plano para sa pag-unlad nito.

---

## 🛠️ Ano ang mga Bagong Features? (Current Updates)

Narito ang mga mahahalagang pagbabagong ginawa para maging maayos ang takbo ng system sa iba't ibang branches ng UCC.

### 1. Main Branch Control (Campus Management)
- **Ano ito?** Isang dashboard para sa **Head Librarian** kung saan pwede siyang magdagdag o mag-alis ng mga campus (halimbawa: UCC Congressional, South, o North).
- **Benepisyo:** Kapag ang isang campus ay pansamantalang isinara sa system, automatic na mawawala sa listahan ang mga libro at tao nito para hindi makagulo sa inventory ng ibang branch.

### 2. Smart Branch Filtering (Equipment at Books)
- **Ano ito?** Automatic na nililimitahan ng system ang nakikitang gamit at libro depende sa kung saang branch naka-assign ang Librarian.
- **Benepisyo:** Kung ikaw ay taga-UCC South, hindi mo na kailangang mag-scroll sa libo-libong libro ng ibang branch. Ang makikita mo lang ay ang mga gamit na nasa loob ng iyong library.

### 3. Mas Ligtas na Student Promotion
- **Ano ito?** Ang paglilipat ng level ng mga estudyante (hal. mula 1st year patungong 2nd year) ay maaari nang gawin "per campus."
- **Benepisyo:** Maiiwasan ang pagkakamali kung saan ang mga estudyante sa kabilang branch ay nadadamay sa pag-update ng records ng ibang branch.

---

## 🔄 Kumusta ang mga Transaksyon? (Core Operations)

Inayos din natin ang mga pangunahing proseso ng library para maging "Campus-Aware" o marunong kumilala ng branch.

### 4. Paghiram at QR Scanning (Borrowing)
- **Ano ang bago?** Sa bawat scan ng QR code ng estudyante, automatic nang ipinapakita ng system kung anong branch ang "Home Campus" ng estudyante at kung saang branch kabilang ang librong hiram niya.
- **Iwas Lito:** Agad na makikita ng Librarian kung ang libro ay galing sa kabilang branch para mabigyan ng paalala ang estudyante kung saan ito dapat isauli.

### 5. Pagsasauli ng Libro (Returning)
- **Ano ang bago?** Sa bawat pagsasauli, naitatala na kung saang branch physical na ibinalik ang libro.
- **Mas Madaling Paghahanap:** Kung sakaling "mawala" ang libro, makikita sa history kung saang branch ito huling hinawakan o tinanggap ng Librarian.

### 6. Listahan at Reports (Transaction History)
- **Ano ang bago?** Ang lahat ng listahan ng hiniram at binalik ay may "Campus Label" na. Ang mga reports (sino ang top borrower, anong librong sikat) ay pwede nang i-filter per branch.
- **Para sa Head Librarian:** Mas madali nang gumawa ng ulat kung aling branch ang pinaka-active at aling branch ang nangangailangan ng dagdag na koleksyon ng libro.

---

## 🚀 Ano ang mga Susunod na Plano? (Future Roadmap)

### 🔴 Phase 1: Paglilinis ng Records at Security
- **Active/Inactive Toggle:** Sa halip na tuluyang "i-delete" ang mga records, lalagyan sila ng switch. Mas madali itong i-manage at hindi mawawala ang data para sa audit purposes.
- **Login Protection:** Hindi na makaka-login ang mga users at staff kung ang kanilang home campus ay naka-set bilang "Inactive."

### 🟠 Phase 2: Kanya-kanyang Patakaran (Policies)
- **Per-Campus Rules:** Papayagan ang bawat branch na magkaroon ng sariling limit. Halimbawa: Sa Main Branch ay 5 books max, pero sa mas maliit na branch ay 3 books lang para mas marami ang makinabang sa kakaunting kopya.

### 🟡 Phase 3: Inter-Campus Logistics
- **"Return Anywhere":** Plano nating payagan ang mga estudyante na magbalik sa kahit saang branch, at ang system na ang bahalang mag-track kung nasaan na ang libro.
- **Inter-branch Transfer:** Isang sistema para sa pag-request ng libro mula sa ibang branch para ipadala sa branch na malapit sa estudyante.

---

## 👥 Sino ang mga Gumagamit at Ano ang Gagawin Nila?

| Role | Ano ang Scope ng Trabaho? |
|------|--------------------------|
| **Superadmin (Head Librarian)** | Nakakakita ng LAHAT ng branches. Siya ang nag-aayos ng listahan ng campuses at system backups. |
| **Admin (Assistant Head)** | Nakakakita ng LAHAT ng branches. Siya ang tumutulong sa pag-update ng listahan ng mga estudyante. |
| **Campus Admin (Branch Head)** | Nakatutok lang sa SARILING campus. Siya ang nag-mamanage ng inventory at staff ng kanyang branch. |
| **Librarian (Staff)** | Nag-aasikaso ng hiram at balik sa counter ng SARILING campus. |

---
*Ang LibSys v3.0 ay patuloy na ina-update upang mas mapadali ang serbisyo sa ating mga mag-aaral at kawani.*
