import fs from "node:fs";
import path from "node:path";

const translations = {
	"de_DE": {
		"Add MCP server": "MCP-Server hinzufügen",
		"Add MCP server in DocsBot": "MCP-Server in DocsBot hinzufügen",
		"Add skill": "Skill hinzufügen",
		"Add skill in DocsBot": "Skill in DocsBot hinzufügen",
		"Allow the bot to detect when a user needs to speak to a human.": "Dem Bot erlauben zu erkennen, wann ein Benutzer mit einem Menschen sprechen möchte.",
		"Button Label": "Schaltflächenbeschriftung",
		"Button Link": "Schaltflächenlink",
		"Collect Feedback": "Feedback erfassen",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Bewertungen (CSAT) von Benutzern nach der Interaktion mit dem Bot erfassen.",
		"Connect your bot to external tools and data from your services.": "Verbinden Sie Ihren Bot mit externen Tools und Daten aus Ihren Diensten.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "Die DocsBot-Authentifizierung ist fehlgeschlagen. Ersetzen Sie den API-Schlüssel und stellen Sie die Verbindung erneut her.",
		"Custom Buttons": "Benutzerdefinierte Schaltflächen",
		"Enable bot skills to give your bot special abilities.": "Aktivieren Sie Bot-Skills, um Ihrem Bot besondere Fähigkeiten zu verleihen.",
		"Human Support Escalation": "Weiterleitung an menschlichen Support",
		"Let your bot show a configured button when its instructions match.": "Lassen Sie Ihren Bot eine konfigurierte Schaltfläche anzeigen, wenn die Anweisungen zutreffen.",
		"MCP Servers": "MCP-Server",
		"New!": "Neu!",
		"Scheduling Tools": "Planungstools",
		"Skills": "Skills",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Öffnen Sie ein eingebettetes Buchungs-Widget für Calendly, Cal.com oder TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Dieser API-Schlüssel hat keine Berechtigung für diesen DocsBot-Vorgang. Bitten Sie einen Team-Inhaber oder Administrator um den erforderlichen Bot-Zugriff.",
		"Web Search": "Websuche",
	},
	"es_ES": {
		"Add MCP server": "Añadir servidor MCP",
		"Add MCP server in DocsBot": "Añadir servidor MCP en DocsBot",
		"Add skill": "Añadir habilidad",
		"Add skill in DocsBot": "Añadir habilidad en DocsBot",
		"Allow the bot to detect when a user needs to speak to a human.": "Permite que el bot detecte cuándo un usuario necesita hablar con una persona.",
		"Button Label": "Etiqueta del botón",
		"Button Link": "Enlace del botón",
		"Collect Feedback": "Recopilar comentarios",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Recopila valoraciones (CSAT) de los usuarios después de interactuar con el bot.",
		"Connect your bot to external tools and data from your services.": "Conecta tu bot a herramientas externas y datos de tus servicios.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "La autenticación de DocsBot ha fallado. Sustituye la clave de API y vuelve a conectar.",
		"Custom Buttons": "Botones personalizados",
		"Enable bot skills to give your bot special abilities.": "Activa habilidades para dar a tu bot capacidades especiales.",
		"Human Support Escalation": "Escalado a soporte humano",
		"Let your bot show a configured button when its instructions match.": "Permite que tu bot muestre un botón configurado cuando se cumplan sus instrucciones.",
		"MCP Servers": "Servidores MCP",
		"New!": "¡Nuevo!",
		"Scheduling Tools": "Herramientas de programación",
		"Skills": "Habilidades",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Abre un widget de reservas integrado para Calendly, Cal.com o TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Esta clave de API no tiene permiso para esa operación de DocsBot. Solicita al propietario o administrador del equipo el acceso necesario al bot.",
		"Web Search": "Búsqueda web",
	},
	"fr_FR": {
		"Add MCP server": "Ajouter un serveur MCP",
		"Add MCP server in DocsBot": "Ajouter un serveur MCP dans DocsBot",
		"Add skill": "Ajouter une compétence",
		"Add skill in DocsBot": "Ajouter une compétence dans DocsBot",
		"Allow the bot to detect when a user needs to speak to a human.": "Permet au bot de détecter lorsqu’un utilisateur souhaite parler à une personne.",
		"Button Label": "Libellé du bouton",
		"Button Link": "Lien du bouton",
		"Collect Feedback": "Recueillir des avis",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Recueille les évaluations (CSAT) des utilisateurs après leur interaction avec le bot.",
		"Connect your bot to external tools and data from your services.": "Connectez votre bot aux outils externes et aux données de vos services.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "L’authentification DocsBot a échoué. Remplacez la clé API et reconnectez-vous.",
		"Custom Buttons": "Boutons personnalisés",
		"Enable bot skills to give your bot special abilities.": "Activez les compétences du bot pour lui donner des capacités particulières.",
		"Human Support Escalation": "Transfert vers l’assistance humaine",
		"Let your bot show a configured button when its instructions match.": "Permet à votre bot d’afficher un bouton configuré lorsque ses instructions correspondent.",
		"MCP Servers": "Serveurs MCP",
		"New!": "Nouveau !",
		"Scheduling Tools": "Outils de planification",
		"Skills": "Compétences",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Ouvre un widget de réservation intégré pour Calendly, Cal.com ou TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Cette clé API n’est pas autorisée à effectuer cette opération DocsBot. Demandez au propriétaire ou à l’administrateur de l’équipe l’accès requis au bot.",
		"Web Search": "Recherche Web",
	},
	"ja": {
		"Add MCP server": "MCPサーバーを追加",
		"Add MCP server in DocsBot": "DocsBotでMCPサーバーを追加",
		"Add skill": "スキルを追加",
		"Add skill in DocsBot": "DocsBotでスキルを追加",
		"Allow the bot to detect when a user needs to speak to a human.": "ユーザーが担当者との会話を必要としていることをボットが検出できるようにします。",
		"Button Label": "ボタンのラベル",
		"Button Link": "ボタンのリンク",
		"Collect Feedback": "フィードバックを収集",
		"Collect ratings (CSAT) from users after they interact with the bot.": "ボットとの対話後にユーザーから評価（CSAT）を収集します。",
		"Connect your bot to external tools and data from your services.": "ボットを外部ツールやサービスのデータに接続します。",
		"DocsBot authentication failed. Replace the API key and reconnect.": "DocsBotの認証に失敗しました。APIキーを交換して再接続してください。",
		"Custom Buttons": "カスタムボタン",
		"Enable bot skills to give your bot special abilities.": "ボットスキルを有効にして、特別な機能を追加します。",
		"Human Support Escalation": "有人サポートへのエスカレーション",
		"Let your bot show a configured button when its instructions match.": "指示に一致したときに、設定済みのボタンをボットに表示させます。",
		"MCP Servers": "MCPサーバー",
		"New!": "新機能",
		"Scheduling Tools": "予約ツール",
		"Skills": "スキル",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Calendly、Cal.com、TidyCalの埋め込み予約ウィジェットを開きます。",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "このAPIキーには、そのDocsBot操作を行う権限がありません。必要なボットアクセス権をチームの所有者または管理者に依頼してください。",
		"Web Search": "ウェブ検索",
	},
	"pt_BR": {
		"Add MCP server": "Adicionar servidor MCP",
		"Add MCP server in DocsBot": "Adicionar servidor MCP no DocsBot",
		"Add skill": "Adicionar habilidade",
		"Add skill in DocsBot": "Adicionar habilidade no DocsBot",
		"Allow the bot to detect when a user needs to speak to a human.": "Permite que o bot detecte quando um usuário precisa falar com uma pessoa.",
		"Button Label": "Rótulo do botão",
		"Button Link": "Link do botão",
		"Collect Feedback": "Coletar feedback",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Coleta avaliações (CSAT) dos usuários após a interação com o bot.",
		"Connect your bot to external tools and data from your services.": "Conecte seu bot a ferramentas externas e dados dos seus serviços.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "A autenticação do DocsBot falhou. Substitua a chave de API e reconecte.",
		"Custom Buttons": "Botões personalizados",
		"Enable bot skills to give your bot special abilities.": "Ative habilidades para dar recursos especiais ao seu bot.",
		"Human Support Escalation": "Escalonamento para suporte humano",
		"Let your bot show a configured button when its instructions match.": "Permite que seu bot mostre um botão configurado quando as instruções correspondentes forem atendidas.",
		"MCP Servers": "Servidores MCP",
		"New!": "Novo!",
		"Scheduling Tools": "Ferramentas de agendamento",
		"Skills": "Habilidades",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Abre um widget de agendamento incorporado para Calendly, Cal.com ou TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Esta chave de API não tem permissão para essa operação do DocsBot. Solicite ao proprietário ou administrador da equipe o acesso necessário ao bot.",
		"Web Search": "Pesquisa na web",
	},
	"zh_CN": {
		"Add MCP server": "添加 MCP 服务器",
		"Add MCP server in DocsBot": "在 DocsBot 中添加 MCP 服务器",
		"Add skill": "添加技能",
		"Add skill in DocsBot": "在 DocsBot 中添加技能",
		"Allow the bot to detect when a user needs to speak to a human.": "允许机器人检测用户何时需要与人工客服交谈。",
		"Button Label": "按钮标签",
		"Button Link": "按钮链接",
		"Collect Feedback": "收集反馈",
		"Collect ratings (CSAT) from users after they interact with the bot.": "在用户与机器人互动后收集评分（CSAT）。",
		"Connect your bot to external tools and data from your services.": "将机器人连接到外部工具和服务数据。",
		"DocsBot authentication failed. Replace the API key and reconnect.": "DocsBot 身份验证失败。请更换 API 密钥并重新连接。",
		"Custom Buttons": "自定义按钮",
		"Enable bot skills to give your bot special abilities.": "启用机器人技能，为机器人提供特殊能力。",
		"Human Support Escalation": "转接人工支持",
		"Let your bot show a configured button when its instructions match.": "当符合指令时，让机器人显示已配置的按钮。",
		"MCP Servers": "MCP 服务器",
		"New!": "新功能！",
		"Scheduling Tools": "预约工具",
		"Skills": "技能",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "打开 Calendly、Cal.com 或 TidyCal 的嵌入式预约组件。",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "此 API 密钥无权执行该 DocsBot 操作。请向团队所有者或管理员申请所需的机器人访问权限。",
		"Web Search": "网页搜索",
	},
};

function quotePo(value) {
	return `"${value.replaceAll("\\", "\\\\").replaceAll('"', '\\"').replaceAll("\n", "\\n")}"`;
}

const source = "docsbot/includes/class-docsbot-admin.php";
const potFile = path.join("docsbot", "languages", "docsbot.pot");
let pot = fs.readFileSync(potFile, "utf8").trimEnd();
const actionMessages = Object.keys(translations.de_DE);

for (const msgid of actionMessages) {
	const marker = `msgid ${quotePo(msgid)}\n`;
	if (!pot.includes(marker)) {
		pot += `\n\n#: ${source}\nmsgid ${quotePo(msgid)}\nmsgstr ""`;
	}
}

fs.writeFileSync(potFile, `${pot}\n`);

for (const [locale, messages] of Object.entries(translations)) {
	const file = path.join("docsbot", "languages", `docsbot-${locale}.po`);
	let catalog = fs.readFileSync(file, "utf8").trimEnd();

	for (const [msgid, msgstr] of Object.entries(messages)) {
		const escaped = msgid.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
		const pattern = new RegExp(`(^|\\n)((?:#.*\\n)*msgid "${escaped.replaceAll('"', '\\"')}"\\n)msgstr "(?:[^"\\\\]|\\\\.)*"`, "m");
		if (!pattern.test(catalog)) {
			catalog += `\n\n#: ${source}\nmsgid ${quotePo(msgid)}\nmsgstr ${quotePo(msgstr)}`;
			continue;
		}
		catalog = catalog.replace(pattern, (_match, prefix, entry) => {
			const cleaned = entry
				.replace(/^#, ([^\n]*, )?fuzzy(, [^\n]*)?\n/m, (_line, before = "", after = "") => {
					const flags = `${before}${after}`.replace(/^, |, $/g, "");
					return flags ? `#, ${flags}\n` : "";
				});
			return `${prefix}${cleaned}msgstr ${quotePo(msgstr)}`;
		});
	}

	fs.writeFileSync(file, `${catalog}\n`);
}
