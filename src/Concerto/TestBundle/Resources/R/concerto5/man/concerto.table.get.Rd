\name{leap.table.get}
\alias{leap.table.get}
\title{
    Table object retrieval
}
\description{
    Gets table object.
}
\usage{
    leap.table.get(tableId)
}
\arguments{
    \item{tableId}{
        Table object Id or name.
    }
}
\details{
}
\value{
    List with table object properties:
    \item{id}{table object ID}
    \item{name}{table object name}
}
\references{
}
\author{
    Kooky Whiz
}
\note{
}

\seealso{
}
\examples{
    table1 <- leap.table.get(1)
    table2 <- leap.table.get("some_other_table")
}

\keyword{table}
\keyword{database}
