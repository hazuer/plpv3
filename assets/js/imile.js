//Powered By HaZuEr.Ing
//Version:14102025
// Solicitar los números de seguimiento mediante un prompt
const input = prompt("👾 Ingresa los números de guía iMile [📦]:");
// Procesar el input para crear el array
const trackingNumbers = input 
    ? input.split('\n')          // Dividir por saltos de línea
           .map(num => num.trim()) // Limpiar espacios
           .filter(num => num !== '') // Eliminar líneas vacías
    : []; // Si no se ingresa nada, array vacío
const color = prompt(`
👾 Color (elige un número) [🎨]:
---------------------------------
🔴[1] red    🟢[3] green
💙[2] blue   ⚫[4] black
---------------------------------`).trim().toLowerCase() || "4";

// Mapear números a nombres de color
const colorMapNumber = {
  '1': 'red',
  '2': 'blue',
  '3': 'green',
  '4': 'black'
};
const colorMap = {
  '1': '🔴',
  '2': '💙',
  '3': '🟢',
  '4': '⚫'
};
// Validación y asignación del color
const colorFinal = colorMapNumber[color] || "black";
// Solicitar ubicación con opciones claras
const id_location = prompt(`
👾 Ingresa el ID de ubicación [📍]:
1 - TQL
2 - ZAC`) || 1;

const id_user = (id_location == 1) ? 2 : 4;  // Si es 1 (TQL), asigna usuario 2 (karen); si no, asigna 4 (josue)

// Generar mensaje de confirmación
const guiaInicial = trackingNumbers[0] || "N/A";
const guiaFinal   = trackingNumbers[trackingNumbers.length - 1] || "N/A";
const totalGuias  = trackingNumbers.length;

const mensajeConfirmacion = `
¿👾 Los datos son correctos? [⚙️]:
---------------------------------
🔢 Total de guías: ${totalGuias}
📦 Guía inicial: ${guiaInicial}
📦 Guía final: ${guiaFinal}
---------------------------------
🎨 Color: ${colorMap[color]}
📍 Ubicación: ${id_location == 1 ? "TQL" : "ZAC"}`;

// Mostrar alerta de confirmación
const isConfirmed = confirm(mensajeConfirmacion);

// Array to store all results
const resultados = [];
let contador = 0;
const totalElementos = trackingNumbers.length;

async function enviarDatos(resultado) {
    try {
        const endpoint = "https://paqueterialospinos.com/controllers/puppeteer.php";
        console.log(`📤 Enviando datos de ${resultado.tracking} al endpoint paqueterialospinos`);
        const response = await page.evaluate(async (url, data) => {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        }, endpoint, resultado);
        console.log("✅ Respuesta del servidor:", response);
        return response; // <--- Devuelve el objeto completo
    } catch (error) {
        console.error("❌ Error al enviar datos:", error);
        return { success: "false", message: "Error de red o excepción" };
    }
}

async function clickTab(page, possibleNames, maxAttempts = 10, delayBetweenAttempts = 5000) {
    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        try {
            const tabs = await page.$$('button.MuiTab-root');

            for (const tab of tabs) {
                const tabText = await page.evaluate(el => el.textContent.trim(), tab);
                if (possibleNames.includes(tabText)) {
                    await tab.click();
                    console.log(`✅ ${attempt > 1 ? 'Reintento ' : ''}Clic en pestaña: ${tabText}`);
                    await page.waitForTimeout(1500); // Pequeña espera para estabilización
                    return true;
                }
            }
            if (attempt < maxAttempts) {
                console.log(`⏳ Intento ${attempt} fallido. Reintentando...`);
                await page.waitForTimeout(delayBetweenAttempts);
            }
        } catch (error) {
            console.error(`🔴 Error en intento ${attempt}:`, error.message);
        }
    }

    console.error(`❌ No se encontró ninguna pestaña válida después de ${maxAttempts} intentos`);
    return false;
}

if (isConfirmed) {
    for (const trackingNumber of trackingNumbers) {
        contador++;
        const resultado = {
            option       : "store",
            id_location  : id_location,
            phone        : "",
            receiver     : "",
            address      : "",
            id_user      : id_user,
            tracking     : trackingNumber,
            id_cat_parcel: 2, //iMile
            marker       : colorFinal,
            estado       : ""
        };
        try {
            await page.goto(`https://ds.imile.com/#/DSOperation/WaybillManagement/dsTrackQuery?waybillNo=${trackingNumber}`);
            await page.reload();

            console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
            console.log(`:::::::::::::::::: Procesando ${contador} de ${totalElementos} ::::::::::::::::::`);
            console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
            await page.evaluate(() => console.clear());

            const possibleNames = [
                "Recipiente de información",
                "Cliente Info",
                "Customer Info"
            ];

            await clickTab(page, possibleNames);

            
// --- NUEVO BLOQUE A IMPLEMENTAR ---

let tel_entrante = null;
let contact_name = null;
// Mapeo de posibles variaciones para cada campo
const phoneLabels = ['Teléfono entrante', 'Customer phone'];
const nameLabels = ['Contacto del destinatario', 'Customer Name'];
try {
    // 1. PRIMERO: Hacer clic en los íconos del ojo para teléfono y nombre
    console.log(`👁️ Buscando íconos del ojo...`);
    const labels = await page.$$('.detail-item .label');
    // Hacer clic en el ícono del teléfono
    let phoneIconClicked = false;
    let nameIconClicked = false;
    for (const label of labels) {
        const labelText = await page.evaluate(el => el.textContent, label);
        const normalizedLabel = labelText
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .toLowerCase();
        // Verificar si es una etiqueta de teléfono
        const isPhoneLabel = phoneLabels.some(phoneLabel => 
            normalizedLabel.includes(phoneLabel.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase())
        );
        // Verificar si es una etiqueta de nombre
        const isNameLabel = nameLabels.some(nameLabel => 
            normalizedLabel.includes(nameLabel.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase())
        );
        // Buscar el SVG dentro de este label
        const svg = await label.$('svg');
        if (svg) {
            if (isPhoneLabel && !phoneIconClicked) {
                console.log(`✅ Etiqueta de teléfono encontrada: "${labelText}"`);
                console.log(`🎯 Haciendo clic en ícono del teléfono...`);
                await svg.click();
                await page.waitForTimeout(2000);
                phoneIconClicked = true;
                console.log(`✅ Clic realizado en ícono del teléfono`);
            }
            if (isNameLabel && !nameIconClicked) {
                console.log(`✅ Etiqueta de nombre encontrada: "${labelText}"`);
                console.log(`🎯 Haciendo clic en ícono del nombre...`);
                await svg.click();
                await page.waitForTimeout(2000);
                nameIconClicked = true;
                console.log(`✅ Clic realizado en ícono del nombre`);
            }
        }
        // Si ya hicimos clic en ambos, salir del loop
        if (phoneIconClicked && nameIconClicked) {
            break;
        }
    }
    if (!phoneIconClicked) {
        console.log(`❌ No se pudo hacer clic en el ícono del teléfono`);
    }
    if (!nameIconClicked) {
        console.log(`❌ No se pudo hacer clic en el ícono del nombre`);
    }
    // 2. SEGUNDO: Buscar los datos actualizados
    console.log(`🔍 Buscando datos actualizados...`);
    const elements = await page.$$('.detail-item');
    for (const element of elements) {
        try {
            const labelElement = await element.$('.label');
            const valueElement = await element.$('.value');
            if (labelElement && valueElement) {
                const label = await page.evaluate(el => el.textContent.trim(), labelElement);
                const value = await page.evaluate(el => el.textContent.trim(), valueElement);
                // Normalizar la etiqueta para comparación
                const normalizedLabel = label
                    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                    .toLowerCase();
                // Buscar coincidencias para teléfono
                if (phoneLabels.some(phoneLabel => 
                    normalizedLabel.includes(phoneLabel.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase()))) {
                    if (value !== '****' && value.trim() !== '' && value !== '-') {
                        tel_entrante = value.replace(/\D/g, '').slice(-10);
                        console.log(`📞 Teléfono encontrado: ${tel_entrante}`);
                    } else {
                        console.log(`🔒 Teléfono aún oculto: "${value}"`);
                    }
                }
                // Buscar coincidencias para nombre
                if (nameLabels.some(nameLabel => 
                    normalizedLabel.includes(nameLabel.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase()))) {
                    if (value !== '****' && value.trim() !== '' && value !== '-') {
                        contact_name = value;
                        console.log(`👤 Nombre encontrado: ${contact_name}`);
                    } else {
                        console.log(`🔒 Nombre aún oculto: "${value}"`);
                    }
                }
            }
        } catch (error) {
            console.log('Error procesando elemento:', error.message);
        }
    }
} catch (error) {
    console.error('Error al buscar elementos:', error.message);
}
// --- FIN DEL NUEVO BLOQUE ---

            const errores = [];
            // Validar teléfono (10 dígitos exactos)
            if (!tel_entrante || !/^\d{10}$/.test(tel_entrante)) {
                errores.push('Teléfono inválido o no encontrado');
                tel_entrante = null; // Forzar a null si no cumple el formato
            }

            if (errores.length === 0) {
                resultado.receiver = contact_name;
                resultado.phone    = tel_entrante;
                console.log(`✅ Datos válidos: ${contact_name} | ${tel_entrante}`);

                try {
                    // Envío al endpoint con timeout
                    const respuestaServidor = await Promise.race([
                        enviarDatos(resultado),
                        new Promise((_, reject) => 
                            setTimeout(() => reject(new Error('Timeout excedido')), 10000)
                        )
                    ]);

                    if (respuestaServidor?.success === true || respuestaServidor?.success === "true") {
                        resultado.estado = "Registrado";
                        console.log('📌 Registro exitoso en el servidor');
                    } else {
                        const msg = respuestaServidor?.message?.replace(/["']/g, "") || "Error sin especificar";
                        resultado.estado = "Falló: " + msg;
                        console.error('❌ Error del servidor:', msg);
                    }
                } catch (error) {
                    resultado.estado = "Falló: Error de conexión";
                    console.error('🚨 Error al enviar al endpoint:', error.message);
                }
            } else {
                resultado.estado = "Falló: " + errores.join(' - ');
                console.error('❌ Datos incompletos:', errores.join(' | '));
            }
            //await page.evaluate(() => console.clear());

        } catch (error) {
            console.error(`❌ Error al procesar ${trackingNumber}:`, error.message);
            resultado.estado = `Falló: ${error.message}`;
        } finally {
            resultados.push(resultado);
        }
    }
    await page.waitForTimeout(1000);
    console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
    console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
    console.log("📊 FIN DEL PROCESO:");
    // Filtrar y contar resultados
    const guiasRegistradas = resultados.filter(r => r.estado === "Registrado");
    const guiasConError    = resultados.filter(r => r.estado !== "Registrado" && r.estado.includes("Falló")); // Asegura que solo cuente los fallos reales
    console.log(`📦 Total procesado: ${resultados.length}`);
    console.log(`✅ Guías registradas correctamente: ${guiasRegistradas.length}`);
    if (guiasConError.length > 0) {
        console.log(`❌ Guías con errores: ${guiasConError.length}`);
        console.log("\n🔍 Detalle de errores:");
        guiasConError.forEach((resultado, index) => {
            console.log(`\n${index + 1}. Guía: ${resultado.tracking}`);
            console.log(`Estado: ${resultado.estado}`);
            console.log(`Receptor: ${resultado.receiver || "No disponible"}`);
            console.log(`Teléfono: ${resultado.phone || "No disponible"}`);
        });
    }
    console.clear();
} else {
    console.log("❌ Proceso cancelado por el usuario");
}