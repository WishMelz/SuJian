import "./ui.css"
import { useEffect, useLayoutEffect, useRef, useState } from "react"
import { createRoot } from "react-dom/client"
import { ArrowUp, Menu, Monitor, Moon, Search, Sun } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog"
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription, SheetTrigger } from "@/components/ui/sheet"
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip"
import { Input } from "@/components/ui/input"

type Theme = "light" | "dark" | "system"
type NavigationLink = { label: string; href: string; target: string; rel: string; current: boolean }
type SiteConfig = {
	action: string
	title: string
	links: NavigationLink[]
	mount: HTMLElement
	initialSearchOpen: boolean
	initialSearchValue: string
	initialMenuOpen: boolean
}

function storedTheme(): Theme {
	try {
		const value = localStorage.getItem("sujian-color-scheme") || localStorage.getItem("wish-color-scheme")
		return value === "light" || value === "dark" ? value : "system"
	} catch {
		return "system"
	}
}

function HeaderTools({ action, title, links, mount, initialSearchOpen, initialSearchValue, initialMenuOpen }: SiteConfig) {
	const [searchOpen, setSearchOpen] = useState(initialSearchOpen)
	const [menuOpen, setMenuOpen] = useState(initialMenuOpen)
	const [theme, setTheme] = useState<Theme>(storedTheme)
	const searchTrigger = useRef<HTMLButtonElement>(null)
	const searchInput = useRef<HTMLInputElement>(null)
	const menuTrigger = useRef<HTMLButtonElement>(null)
	const themeOptions = [
		{ value: "light", label: "浅色", Icon: Sun },
		{ value: "dark", label: "深色", Icon: Moon },
		{ value: "system", label: "跟随系统", Icon: Monitor },
	] as const
	const ThemeIcon = theme === "system" ? Monitor : theme === "dark" ? Moon : Sun

	useLayoutEffect(() => {
		document.querySelectorAll<HTMLElement>("[data-ui-fallback], [data-search-panel], [data-mobile-nav]").forEach(element => { element.hidden = true })
		document.documentElement.dataset.uiReady = "true"
		mount.hidden = false
	}, [mount])

	useEffect(() => {
		const system = matchMedia("(prefers-color-scheme: dark)")
		const applyTheme = () => {
			const resolved = theme === "system" ? (system.matches ? "dark" : "light") : theme
			document.documentElement.dataset.theme = resolved
			document.querySelector('meta[name="theme-color"]')?.setAttribute("content", resolved === "dark" ? "#09090b" : "#ffffff")
		}
		applyTheme()
		system.addEventListener("change", applyTheme)
		return () => system.removeEventListener("change", applyTheme)
	}, [theme])

	useEffect(() => {
		const syncTheme = (event: StorageEvent) => {
			if (!event.key || event.key === "sujian-color-scheme" || event.key === "wish-color-scheme") setTheme(storedTheme())
		}
		const openSearch = (event: KeyboardEvent) => {
			const target = event.target
			if (event.defaultPrevented || event.isComposing || event.ctrlKey || event.metaKey || event.altKey) return
			if (target instanceof HTMLElement && target.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"]), [role="textbox"], [role="dialog"], [role="menu"]')) return
			if (event.key === "/") {
				event.preventDefault()
				setMenuOpen(false)
				setSearchOpen(true)
			}
		}
		const desktop = matchMedia("(min-width: 721px)")
		const closeMenu = () => { if (desktop.matches) setMenuOpen(false) }
		window.addEventListener("storage", syncTheme)
		document.addEventListener("keydown", openSearch)
		desktop.addEventListener("change", closeMenu)
		return () => {
			window.removeEventListener("storage", syncTheme)
			document.removeEventListener("keydown", openSearch)
			desktop.removeEventListener("change", closeMenu)
		}
	}, [])

	function chooseTheme(value: string) {
		if (value !== "light" && value !== "dark" && value !== "system") return
		try {
			localStorage.setItem("sujian-color-scheme", value)
			localStorage.removeItem("wish-color-scheme")
		} catch {}
		setTheme(value)
	}

	return (
		<TooltipProvider delay={350}>
			<Dialog open={searchOpen} onOpenChange={setSearchOpen}>
				<Tooltip>
					<TooltipTrigger render={<DialogTrigger render={<Button ref={searchTrigger} variant="ghost" size="icon" className="ui-icon-button" aria-label="打开搜索" />} />}>
						<Search aria-hidden="true" />
					</TooltipTrigger>
					<TooltipContent side="bottom">搜索</TooltipContent>
				</Tooltip>
				<DialogContent className="sujian-ui-panel ui-search-dialog" initialFocus={searchInput} finalFocus={searchTrigger}>
					<DialogHeader>
						<DialogTitle>搜索文章</DialogTitle>
						<DialogDescription className="sr-only">站内搜索</DialogDescription>
					</DialogHeader>
					<form action={action} method="get" role="search" className="ui-search-form">
						<label className="sr-only" htmlFor="sujian-search">关键词</label>
						<Input ref={searchInput} id="sujian-search" name="s" type="search" placeholder="搜索文章" autoComplete="off" defaultValue={initialSearchValue} required />
						<Button type="submit" className="ui-submit"><Search aria-hidden="true" />搜索</Button>
					</form>
				</DialogContent>
			</Dialog>
			<DropdownMenu>
				<Tooltip>
					<TooltipTrigger render={<DropdownMenuTrigger render={<Button variant="ghost" size="icon" className="ui-icon-button" aria-label="选择外观" />} />}>
						<ThemeIcon aria-hidden="true" />
					</TooltipTrigger>
					<TooltipContent side="bottom">外观</TooltipContent>
				</Tooltip>
				<DropdownMenuContent align="end" sideOffset={8} className="sujian-ui-panel ui-theme-menu">
					<DropdownMenuRadioGroup value={theme} onValueChange={chooseTheme} aria-label="外观">
						{themeOptions.map(({ value, label, Icon }) => <DropdownMenuRadioItem key={value} value={value} closeOnClick><Icon aria-hidden="true" />{label}</DropdownMenuRadioItem>)}
					</DropdownMenuRadioGroup>
				</DropdownMenuContent>
			</DropdownMenu>
			<Sheet open={menuOpen} onOpenChange={setMenuOpen}>
				<Tooltip>
					<TooltipTrigger render={<SheetTrigger render={<Button ref={menuTrigger} variant="ghost" size="icon" className="ui-icon-button ui-mobile-menu" aria-label="打开菜单" />} />}>
						<Menu aria-hidden="true" />
					</TooltipTrigger>
					<TooltipContent side="bottom">导航</TooltipContent>
				</Tooltip>
				<SheetContent className="sujian-ui-panel ui-navigation-sheet" finalFocus={menuTrigger}>
					<SheetHeader>
						<SheetTitle>{title}</SheetTitle>
						<SheetDescription className="sr-only">主导航</SheetDescription>
					</SheetHeader>
					<nav className="ui-navigation" aria-label="移动端导航">
						{links.map((link, index) => <a key={`${link.href}-${index}`} href={link.href} target={link.target || undefined} rel={link.rel || undefined} aria-current={link.current ? "page" : undefined} onClick={() => setMenuOpen(false)}>{link.label}</a>)}
					</nav>
				</SheetContent>
			</Sheet>
		</TooltipProvider>
	)
}

function BackToTop() {
	const [visible, setVisible] = useState(false)

	useEffect(() => {
		let frame: number | null = null
		const updateVisibility = () => {
			setVisible(window.scrollY > 400)
			frame = null
		}
		const scheduleUpdate = () => {
			if (frame === null) frame = window.requestAnimationFrame(updateVisibility)
		}
		updateVisibility()
		window.addEventListener("scroll", scheduleUpdate, { passive: true })
		window.addEventListener("pageshow", scheduleUpdate)
		return () => {
			window.removeEventListener("scroll", scheduleUpdate)
			window.removeEventListener("pageshow", scheduleUpdate)
			if (frame !== null) window.cancelAnimationFrame(frame)
		}
	}, [])

	function scrollToTop() {
		document.getElementById("main")?.focus({ preventScroll: true })
		const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches
		window.scrollTo({ top: 0, left: 0, behavior: reducedMotion ? "instant" : "smooth" })
	}

	if (!visible) return null

	return (
		<TooltipProvider delay={350}>
			<Tooltip>
				<TooltipTrigger render={<Button variant="outline" size="icon" className="ui-back-to-top" aria-label="回到顶部" onClick={scrollToTop} />}>
					<ArrowUp aria-hidden="true" />
				</TooltipTrigger>
				<TooltipContent side="left">回到顶部</TooltipContent>
			</Tooltip>
		</TooltipProvider>
	)
}

const mount = document.getElementById("sujian-controls")
const searchForm = document.querySelector<HTMLFormElement>("[data-search-panel] form")
const uiStylesReady = getComputedStyle(document.documentElement).getPropertyValue("--sujian-ui-ready").trim() === "1"
if (mount && searchForm && uiStylesReady) {
	const fallbackSearchPanel = document.querySelector<HTMLElement>("[data-search-panel]")
	const fallbackSearchInput = fallbackSearchPanel?.querySelector<HTMLInputElement>("input")
	const fallbackMobileNav = document.querySelector<HTMLElement>("[data-mobile-nav]")
	const links = Array.from(document.querySelectorAll<HTMLAnchorElement>(".desktop-nav a"), link => ({
		label: link.textContent || "", href: link.href, target: link.target, rel: link.rel, current: link.getAttribute("aria-current") === "page",
	}))
	const title = document.querySelector(".brand-copy strong")?.textContent || "SuJian"
	createRoot(mount).render(<HeaderTools
		mount={mount}
		action={searchForm.action}
		title={title}
		links={links}
		initialSearchOpen={!!fallbackSearchPanel && !fallbackSearchPanel.hidden}
		initialSearchValue={fallbackSearchInput?.value || ""}
		initialMenuOpen={!!fallbackMobileNav && !fallbackMobileNav.hidden}
	/>)
}

const backToTopMount = document.getElementById("sujian-back-to-top")
if (backToTopMount && uiStylesReady) createRoot(backToTopMount).render(<BackToTop />)